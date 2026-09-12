<?php

declare(strict_types=1);

namespace Hypotheek;

/**
 * Een terugkerende maandelijkse kostenpost naast de hypotheek zelf.
 */
final class MonthlyCost
{
    public function __construct(
        public readonly string $omschrijving,
        public readonly float $bedrag,
    ) {
    }
}

/**
 * Samengevatte cijfers van één kalenderjaar.
 */
final class YearRow
{
    public function __construct(
        public readonly int $jaar,
        public readonly int $maanden,
        public readonly float $rente,
        public readonly float $aflossing,
        public readonly float $aftrekbareRente,
        public readonly float $restschuldBegin,
        public readonly float $restschuldEind,
        public readonly float $overigeKosten,
        /** @var array<string, array{rente: float, aflossing: float, restschuld: float}> */
        public readonly array $perLeningdeel,
        /** @var array<int, float> Slotsommen (aflossingsvrij) die in dit jaar opeisbaar worden. */
        public readonly array $slotsommen,
        public readonly ?TaxYearResult $fiscaal,
    ) {
    }

    public function brutoJaarlast(): float
    {
        return $this->rente + $this->aflossing;
    }

    public function brutoMaandlast(): float
    {
        return $this->maanden > 0 ? $this->brutoJaarlast() / $this->maanden : 0.0;
    }

    public function overigeMaandkosten(): float
    {
        return $this->maanden > 0 ? $this->overigeKosten / $this->maanden : 0.0;
    }

    /** Bruto maandlast inclusief de overige maandkosten. */
    public function brutoWoonlastPerMaand(): float
    {
        return $this->brutoMaandlast() + $this->overigeMaandkosten();
    }

    public function belastingvoordeelJaar(): float
    {
        return $this->fiscaal?->voordeelJaar ?? 0.0;
    }

    public function nettoMaandlast(): float
    {
        $voordeelPerMaand = $this->maanden > 0 ? $this->belastingvoordeelJaar() / $this->maanden : 0.0;

        return $this->brutoMaandlast() - $voordeelPerMaand;
    }

    public function nettoWoonlastPerMaand(): float
    {
        return $this->nettoMaandlast() + $this->overigeMaandkosten();
    }

    public function totaleSlotsom(): float
    {
        return array_sum($this->slotsommen);
    }
}

/**
 * Complete uitkomst van de berekening.
 */
final class MortgageResult
{
    /**
     * @param list<Schedule> $schedules
     * @param list<YearRow>  $jaren
     */
    public function __construct(
        public readonly array $schedules,
        public readonly array $jaren,
        public readonly float $totaleHoofdsom,
        public readonly float $totaleRente,
        public readonly float $totaleAflossing,
        public readonly float $totaleSlotsom,
        public readonly float $totaleOverigeKosten,
        public readonly float $totaalBelastingvoordeel,
        public readonly bool $fiscaalBerekend,
        public readonly bool $tarievenBevattenSchatting,
    ) {
    }

    public function eersteMaandBruto(): float
    {
        $totaal = 0.0;
        foreach ($this->schedules as $schedule) {
            $eerste = $schedule->rows[0] ?? null;
            if ($eerste !== null) {
                $totaal += $eerste->bruto();
            }
        }

        return $totaal;
    }

    public function hoogsteBrutoMaandlast(): float
    {
        $perMaand = [];
        foreach ($this->schedules as $schedule) {
            foreach ($schedule->rows as $row) {
                $perMaand[$row->index] = ($perMaand[$row->index] ?? 0.0) + $row->bruto();
            }
        }

        return $perMaand === [] ? 0.0 : max($perMaand);
    }

    public function looptijdMaanden(): int
    {
        $max = 0;
        foreach ($this->schedules as $schedule) {
            $max = max($max, count($schedule->rows));
        }

        return $max;
    }
}

/**
 * Rekent alle leningdelen door en aggregeert per kalenderjaar.
 */
final class Mortgage
{
    /**
     * @param list<LoanPart>    $parts
     * @param list<MonthlyCost> $maandkosten
     * @param array<int,float>|null $inkomenPerJaar jaar => bruto jaarinkomen; null = geen fiscale berekening
     */
    public function __construct(
        private readonly array $parts,
        private readonly int $startJaar,
        private readonly int $startMaand,
        private readonly array $maandkosten = [],
        private readonly ?array $inkomenPerJaar = null,
        private readonly float $wozWaarde = 0.0,
    ) {
    }

    public function bereken(): MortgageResult
    {
        $amortization = new Amortization($this->startJaar, $this->startMaand);
        $schedules = array_map(
            static fn (LoanPart $p): Schedule => $amortization->schedule($p),
            $this->parts
        );

        $maandkostenPerMaand = array_sum(
            array_map(static fn (MonthlyCost $c): float => $c->bedrag, $this->maandkosten)
        );

        $taxCalculator = new TaxCalculator($this->wozWaarde);
        $fiscaalActief = $this->inkomenPerJaar !== null;

        /** @var array<int, array<string, mixed>> $perJaar */
        $perJaar = [];
        foreach ($schedules as $schedule) {
            $naam = $schedule->part->naam;
            foreach ($schedule->rows as $row) {
                $jaar = $row->jaar;
                if (!isset($perJaar[$jaar])) {
                    $perJaar[$jaar] = [
                        'maanden' => 0, 'rente' => 0.0, 'aflossing' => 0.0,
                        'aftrekbaar' => 0.0, 'delen' => [], 'slotsommen' => [],
                        'maandIndexen' => [],
                    ];
                }
                $perJaar[$jaar]['maandIndexen'][$row->index] = true;
                $perJaar[$jaar]['rente'] += $row->rente;
                $perJaar[$jaar]['aflossing'] += $row->aflossing;
                $perJaar[$jaar]['aftrekbaar'] += $row->aftrekbareRente;

                if (!isset($perJaar[$jaar]['delen'][$naam])) {
                    $perJaar[$jaar]['delen'][$naam] = ['rente' => 0.0, 'aflossing' => 0.0, 'restschuld' => 0.0];
                }
                $perJaar[$jaar]['delen'][$naam]['rente'] += $row->rente;
                $perJaar[$jaar]['delen'][$naam]['aflossing'] += $row->aflossing;
                $perJaar[$jaar]['delen'][$naam]['restschuld'] = $row->eindschuld;
            }

            // De slotsom van een aflossingsvrij deel valt in het jaar van de laatste termijn.
            if ($schedule->slotsom > 0.01 && $schedule->rows !== []) {
                $laatste = $schedule->rows[count($schedule->rows) - 1];
                $perJaar[$laatste->jaar]['slotsommen'][$naam] = $schedule->slotsom;
            }
        }

        ksort($perJaar);

        $jaren = [];
        $vorigeRestschuld = array_sum(
            array_map(static fn (Schedule $s): float => $s->part->beginschuld(), $schedules)
        );
        $totaalVoordeel = 0.0;
        $totaalOverig = 0.0;
        $schatting = false;

        foreach ($perJaar as $jaar => $data) {
            $maanden = count($data['maandIndexen']);
            $restschuldEind = array_sum(array_column($data['delen'], 'restschuld'));
            // Delen die in een eerder jaar afliepen tellen niet meer mee; een
            // aflossingsvrij deel wordt bij de slotsom geacht te zijn voldaan.
            $restschuldEind -= array_sum($data['slotsommen']);
            $restschuldEind = max(0.0, $restschuldEind);

            $overigeKosten = $maandkostenPerMaand * $maanden;
            $totaalOverig += $overigeKosten;

            $fiscaal = null;
            if ($fiscaalActief) {
                $inkomen = $this->inkomenPerJaar[$jaar] ?? 0.0;
                $fiscaal = $taxCalculator->bereken($jaar, $inkomen, $data['aftrekbaar']);
                $totaalVoordeel += $fiscaal->voordeelJaar;
                $schatting = $schatting || $fiscaal->tarievenZijnSchatting;
            }

            $jaren[] = new YearRow(
                jaar: $jaar,
                maanden: $maanden,
                rente: $data['rente'],
                aflossing: $data['aflossing'],
                aftrekbareRente: $data['aftrekbaar'],
                restschuldBegin: $vorigeRestschuld,
                restschuldEind: $restschuldEind,
                overigeKosten: $overigeKosten,
                perLeningdeel: $data['delen'],
                slotsommen: $data['slotsommen'],
                fiscaal: $fiscaal,
            );

            $vorigeRestschuld = $restschuldEind;
        }

        return new MortgageResult(
            schedules: $schedules,
            jaren: $jaren,
            totaleHoofdsom: array_sum(
                array_map(static fn (Schedule $s): float => $s->part->beginschuld(), $schedules)
            ),
            totaleRente: array_sum(array_map(static fn (Schedule $s): float => $s->totaleRente(), $schedules)),
            totaleAflossing: array_sum(
                array_map(static fn (Schedule $s): float => $s->totaleAflossing(), $schedules)
            ),
            totaleSlotsom: array_sum(array_map(static fn (Schedule $s): float => $s->slotsom, $schedules)),
            totaleOverigeKosten: $totaalOverig,
            totaalBelastingvoordeel: $totaalVoordeel,
            fiscaalBerekend: $fiscaalActief,
            tarievenBevattenSchatting: $schatting,
        );
    }
}
