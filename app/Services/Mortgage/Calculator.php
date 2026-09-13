<?php

declare(strict_types=1);

namespace App\Services\Mortgage;

use App\Models\RiskClass;
use App\Services\Mortgage\Domain\Amortization;
use App\Services\Mortgage\Domain\LoanPart;
use App\Services\Mortgage\Domain\RateRepository;
use App\Services\Mortgage\Domain\Schedule;

/**
 * De rekenkant van het ontwerp: dezelfde afgeleiden en dezelfde compute() als
 * de browserversie, maar gebouwd op de bestaande domeinklassen.
 *
 * Het aflossingsschema zelf komt uit Amortization; hier zit alleen het
 * samenvoegen per jaar en de fiscale slotsom.
 */
final class Calculator
{
    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    public function __construct(private readonly State $state)
    {
    }

    // ── Afgeleiden ──────────────────────────────────────────────────────────

    public function totalLoan(): float
    {
        if ($this->state->loan !== null) {
            return $this->state->loan;
        }

        return $this->state->isRenew()
            ? $this->state->curBalance
            : max(0.0, $this->state->price - $this->state->own);
    }

    public function homeValue(): float
    {
        return $this->state->isRenew() ? $this->state->woz : $this->state->price;
    }

    /** Loan-to-value in hele procenten, afgetopt op 125. */
    public function ltv(): int
    {
        $prijs = $this->state->price ?: 1.0;

        return (int)min(125, round($this->totalLoan() / $prijs * 100));
    }

    /**
     * Rentekorting die bij de LTV hoort, in procentpunten: alleen nog gebruikt
     * voor de informatieve "korting"-notitie op de bedrag-stap van de wizard.
     * De echte rente voor de berekening komt uit riskClass()/marketRate().
     */
    public function ltvAdj(): float
    {
        $l = $this->ltv();

        return $l <= 60 ? -0.25 : ($l <= 80 ? -0.12 : ($l <= 90 ? -0.04 : 0.0));
    }

    /**
     * De tariefklasse (NHG of een LTV-staffel) waarmee de marktrente wordt
     * opgezocht. Bij oversluiten is de LTV van de nieuwe situatie nog niet
     * relevant voor de tariefonderhandeling, dus geldt het plafond zonder
     * korting - net als voorheen bij ltvAdj().
     */
    public function riskClass(): RiskClass
    {
        if ($this->state->isRenew()) {
            return RateRepository::hoogsteKlasse();
        }

        return RateRepository::klasseVoor($this->ltv(), $this->totalLoan());
    }

    public function marketRate(): float
    {
        return RateRepository::rate($this->state->fixedY, $this->riskClass());
    }

    public function rate(): float
    {
        return $this->state->rate ?? $this->marketRate();
    }

    public function ioRate(): float
    {
        return $this->state->ioRate ?? round(($this->rate() + Constants::ioSurcharge()) * 100) / 100;
    }

    public function ioMax(): float
    {
        $ruw = round($this->homeValue() * Constants::ioMaxShare() / 5000) * 5000;

        return max(0.0, min($this->totalLoan(), $ruw));
    }

    public function io(): float
    {
        return min($this->state->io, $this->ioMax());
    }

    /** Aantal maanden waarover de rente aftrekbaar is. */
    public function dedMonths(): int
    {
        if (!$this->state->isRenew()) {
            return 360;
        }

        $jaren = $this->state->curRemaining ?: 30.0;

        return (int)min(360, $jaren * 12);
    }

    /**
     * De leningdelen zoals het ontwerp ze samenstelt: het aflossende deel en,
     * als er een aflossingsvrij deel is, dat deel apart.
     *
     * @return list<array<string, mixed>>
     */
    public function partsData(): array
    {
        if ($this->state->parts !== null && $this->state->parts !== []) {
            return $this->state->parts;
        }

        $s = $this->state;
        $totaal = $this->totalLoan();
        $io = $this->io();
        $termijn = $s->isRenew() ? (int)min($s->termY, $s->curRemaining ?: $s->termY) : $s->termY;
        $ded = $this->dedMonths();

        $lijst = [];
        if ($totaal - $io > 0) {
            $lijst[] = [
                'id' => 0, 'form' => $s->form, 'sum' => $totaal - $io, 'rate' => $this->rate(),
                'term' => $termijn, 'elapsed' => 0, 'deductible' => true, 'dedMonths' => $ded,
            ];
        }
        if ($io > 0) {
            $lijst[] = [
                'id' => 1, 'form' => 'av', 'sum' => $io, 'rate' => $this->ioRate(),
                'term' => $termijn, 'elapsed' => 0,
                'deductible' => $s->isRenew() && $s->preTwentyThirteen, 'dedMonths' => $ded,
            ];
        }

        if ($lijst === []) {
            $lijst[] = [
                'id' => 0, 'form' => $s->form, 'sum' => 1000.0, 'rate' => $this->rate(),
                'term' => $termijn, 'elapsed' => 0, 'deductible' => true, 'dedMonths' => $ded,
            ];
        }

        return $lijst;
    }

    /** @param array<string, mixed> $deel */
    public static function loanPart(array $deel): LoanPart
    {
        return new LoanPart(
            naam: 'Deel ' . (string)($deel['id'] ?? 0),
            type: Constants::FORM_TYPES[$deel['form']] ?? LoanPart::TYPE_ANNUITAIR,
            hoofdsom: (float)$deel['sum'],
            rentePercentage: (float)$deel['rate'],
            looptijdMaanden: max(1, (int)round(((float)$deel['term']) * 12)),
            reedsVerstrekenMaanden: (int)($deel['elapsed'] ?? 0),
            renteAftrekbaar: !empty($deel['deductible']),
        );
    }

    /** @param array<string, mixed> $deel */
    public static function schedule(array $deel): Schedule
    {
        $ded = (int)($deel['dedMonths'] ?? 360);

        return (new Amortization(2026, 1, $ded))->schedule(self::loanPart($deel));
    }

    /** Bruto eerste maandtermijn van één leningdeel. */
    public static function firstPay(array $deel): float
    {
        $rows = self::schedule($deel)->rows;

        return $rows === [] ? 0.0 : $rows[0]->bruto();
    }

    /** Wat de gebruiker nu betaalt op de lopende hypotheek. */
    public function currentPayment(): float
    {
        $s = $this->state;

        return self::firstPay([
            'form' => $s->curForm, 'sum' => $s->curBalance, 'rate' => $s->curRate,
            'term' => max(1.0, $s->curRemaining), 'elapsed' => 0,
            'deductible' => true, 'dedMonths' => 360,
        ]);
    }

    /** Bruto maandlast als alle delen op deze rente zouden staan. */
    public function atRate(float $rente): float
    {
        $totaal = 0.0;
        foreach ($this->partsData() as $deel) {
            $deel['rate'] = $deel['form'] === 'av' ? $rente + Constants::ioSurcharge() : $rente;
            $totaal += self::firstPay($deel);
        }

        return $totaal;
    }

    /** Bruto maandlast ná de rentevaste periode, bij een renteverschil. */
    public function afterFix(float $delta): float
    {
        $maanden = $this->state->fixedY * 12;
        $totaal = 0.0;

        foreach ($this->partsData() as $deel) {
            $rows = self::schedule($deel)->rows;
            if ($rows === []) {
                continue;
            }
            $idx = (int)min($maanden, count($rows) - 1);
            $schuld = $rows[$idx]->eindschuld;
            if ($schuld <= 0) {
                continue;
            }
            $totaal += self::firstPay([
                'form' => $deel['form'], 'sum' => $schuld, 'rate' => $deel['rate'] + $delta,
                'term' => max(1, (int)$deel['term'] - $this->state->fixedY), 'elapsed' => 0,
                'deductible' => $deel['deductible'], 'dedMonths' => 360,
            ]);
        }

        return $totaal;
    }

    // ── De berekening ───────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function compute(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $delen = $this->partsData();
        $schedules = array_map([self::class, 'schedule'], $delen);

        $maxM = 1;
        foreach ($schedules as $sch) {
            $maxM = max($maxM, count($sch->rows));
        }

        $termY = (int)ceil($maxM / 12);
        $jaren = [];
        for ($y = 0; $y < $termY; $y++) {
            $rente = 0.0;
            $aflossing = 0.0;
            $termijn = 0.0;
            $schuld = 0.0;
            $aftrekbaar = 0.0;
            $maanden = 0;

            for ($m = $y * 12; $m < min(($y + 1) * 12, $maxM); $m++) {
                $maanden++;
                $schuld = 0.0;
                foreach ($schedules as $sch) {
                    $row = $sch->rows[$m] ?? null;
                    if ($row === null) {
                        continue;
                    }
                    $rente      += $row->rente;
                    $aflossing  += $row->aflossing;
                    $termijn    += $row->bruto();
                    $schuld     += $row->eindschuld;
                    $aftrekbaar += $row->aftrekbareRente;
                }
            }

            $jaren[] = [
                'y' => $y + 1, 'interest' => $rente, 'principal' => $aflossing,
                'payment' => $termijn, 'months' => $maanden, 'balance' => $schuld,
                'deductible' => $aftrekbaar,
            ];
        }

        $restschuld = 0.0;
        foreach ($schedules as $sch) {
            $restschuld += $sch->slotsom;
        }

        $kostenTotaal = 0.0;
        foreach ($this->state->kostenLijst() as $post) {
            $kostenTotaal += (float)$post['amount'];
        }

        $inkomen1 = $this->state->income;
        $inkomen2 = $this->state->income2;
        $inkomen  = $inkomen1 + $inkomen2;
        $topInkomen = max($inkomen1, $inkomen2);

        $marginaal = 0.0;
        if ($inkomen > 0) {
            foreach (Constants::brackets() as [$grens, $tarief]) {
                $marginaal = $tarief;
                if ($grens === null || $topInkomen <= $grens) {
                    break;
                }
            }
        }

        $j1 = $jaren[0] ?? ['interest' => 0.0, 'principal' => 0.0, 'payment' => 0.0, 'months' => 12, 'deductible' => 0.0];
        $ewf = $this->state->woz * Constants::ewfRate();
        $aftrekbareRente = $j1['deductible'] * (12 / max(1, $j1['months']));
        $saldo = $ewf - $aftrekbareRente;
        $effTarief = min($marginaal, Constants::capRate()) / 100;

        $voordeel = 0.0;
        $hillen = 0.0;
        if ($inkomen > 0) {
            if ($saldo < 0) {
                $voordeel = -$saldo * $effTarief;
            } else {
                $hillen = $saldo * Constants::hillen();
                $voordeel = -($saldo - $hillen) * ($marginaal / 100);
            }
        }

        $brutoMaand = $j1['payment'] / max(1, $j1['months']);
        $brutoTotaal = $brutoMaand + $kostenTotaal;

        return $this->cache = [
            'parts' => $delen, 'scheds' => $schedules, 'years' => $jaren, 'termY' => $termY,
            'residual' => $restschuld, 'costTotal' => $kostenTotaal,
            'income' => $inkomen, 'income1' => $inkomen1, 'income2' => $inkomen2,
            'marginal' => $marginaal, 'ewf' => $ewf, 'dedInterest' => $aftrekbareRente,
            'hillen' => $hillen, 'benefit' => $voordeel,
            'grossMonthly' => $brutoMaand, 'grossTotal' => $brutoTotaal,
            'netTotal' => $brutoTotaal - $voordeel / 12,
            'interestY1' => $j1['interest'] / max(1, $j1['months']),
            'principalY1' => $j1['principal'] / max(1, $j1['months']),
        ];
    }
}
