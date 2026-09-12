<?php

declare(strict_types=1);

namespace Hypotheek\Design;

/**
 * De state uit het Claude Design-ontwerp, één op één.
 *
 * Het ontwerp houdt deze waarden in het geheugen van de browser bij. Hier komen
 * ze uit de request, zodat de server dezelfde schermen kan renderen als er geen
 * JavaScript is en zodat een URL deelbaar blijft.
 */
final class State
{
    public const PADEN  = ['buy', 'renew'];
    public const VIEWS  = ['wizard', 'calc'];
    public const MODI   = ['simple', 'advanced'];
    public const PLANS  = ['free', 'premium'];
    public const THEMAS = ['light', 'dark'];

    /**
     * @param list<array{id:int,label:string,amount:float}>|null      $costs
     * @param list<array<string,mixed>>|null                          $parts
     */
    public function __construct(
        public string $lang = 'nl',
        public string $theme = 'light',
        public string $view = 'wizard',
        public int $step = 0,
        public string $mode = 'simple',
        public bool $pay = false,
        public string $plan = 'free',
        public string $path = 'buy',
        public float $price = 425000.0,
        public float $own = 60000.0,
        public float $curBalance = 265000.0,
        public float $curRate = 2.10,
        public float $curRemaining = 22.0,
        public string $curForm = 'ann',
        public bool $preTwentyThirteen = false,
        public int $termY = 30,
        public int $fixedY = 10,
        public ?float $rate = null,
        public string $form = 'ann',
        public float $io = 0.0,
        public ?float $ioRate = null,
        public float $income = 0.0,
        public float $income2 = 0.0,
        public float $woz = 425000.0,
        public ?array $costs = null,
        public ?array $parts = null,
        public ?float $loan = null,
        public ?int $lender = null,
        public int $nextId = 3,
    ) {
    }

    /**
     * Leest de state uit een request-array (GET of POST).
     *
     * @param array<string, mixed> $in
     */
    public static function uitRequest(array $in): self
    {
        $s = new self();

        $s->lang  = Translations::normaliseerTaal(self::str($in, 'lang'));
        $s->theme = self::keuze(self::str($in, 'theme'), self::THEMAS, 'light');
        $s->view  = self::keuze(self::str($in, 'view'), self::VIEWS, 'wizard');
        $s->mode  = self::keuze(self::str($in, 'mode'), self::MODI, 'simple');
        $s->plan  = self::keuze(self::str($in, 'plan'), self::PLANS, 'free');
        $s->path  = self::keuze(self::str($in, 'path'), self::PADEN, 'buy');
        $s->pay   = !empty($in['pay']);

        $s->curForm = self::keuze(self::str($in, 'curForm'), Constants::FORMS, 'ann');
        $s->form    = self::keuze(self::str($in, 'form'), Constants::FORMS, 'ann');

        $s->price      = self::getal($in, 'price', $s->price, 0, 100_000_000);
        $s->own        = self::getal($in, 'own', $s->own, 0, 100_000_000);
        $s->curBalance = self::getal($in, 'curBalance', $s->curBalance, 0, 100_000_000);
        $s->curRate    = self::getal($in, 'curRate', $s->curRate, 0, 25);
        $s->curRemaining = self::getal($in, 'curRemaining', $s->curRemaining, 1, 50);
        $s->io         = self::getal($in, 'io', $s->io, 0, 100_000_000);
        $s->income     = self::getal($in, 'income', $s->income, 0, 100_000_000);
        $s->income2    = self::getal($in, 'income2', $s->income2, 0, 100_000_000);
        $s->woz        = self::getal($in, 'woz', $s->woz, 0, 100_000_000);

        $s->termY  = (int)self::getal($in, 'termY', (float)$s->termY, 5, 30);
        $s->fixedY = (int)self::getal($in, 'fixedY', (float)$s->fixedY, 1, 30);
        if (!in_array($s->fixedY, Constants::FIXED_OPTIONS, true)) {
            $s->fixedY = 10;
        }

        $s->preTwentyThirteen = !empty($in['pre2013']);

        if (isset($in['rate']) && $in['rate'] !== '') {
            $s->rate = self::getal($in, 'rate', 0.0, 0.5, 8.0);
        }
        // De schuif stuurt honderdsten van een procent (386 = 3,86%) en wint van
        // het verborgen veld: dat is wat de gebruiker net versleept heeft. Staat
        // hij nog op de marktrente, dan is er niets versleept en blijft de rente
        // meebewegen met de tariefklasse, precies als in het ontwerp.
        if (isset($in['rateSlider']) && $in['rateSlider'] !== '') {
            $geschoven = self::getal($in, 'rateSlider', 386.0, 50.0, 800.0) / 100;
            $markt = (new Calculator($s))->marketRate();
            $s->rate = abs($geschoven - $markt) <= 0.05 ? null : $geschoven;
        }
        if (isset($in['loan']) && $in['loan'] !== '') {
            $s->loan = self::getal($in, 'loan', 0.0, 0, 100_000_000);
        }
        if (isset($in['lender']) && $in['lender'] !== '') {
            $s->lender = (int)self::getal($in, 'lender', 0.0, 0, 99);
        }

        $s->step = (int)self::getal($in, 'step', 0.0, 0, 20);

        $s->costs = self::kosten($in);
        $s->parts = self::delen($in);
        $s->nextId = max(3, (int)self::getal($in, 'nextId', 3.0, 0, 9999));

        return $s;
    }

    public function isRenew(): bool
    {
        return $this->path === 'renew';
    }

    public function isPremium(): bool
    {
        return $this->plan === 'premium';
    }

    /** De stappen van de wizard, afhankelijk van het gekozen pad. */
    public function steps(): array
    {
        return $this->isRenew()
            ? ['start', 'huidig', 'periode', 'rente', 'vorm', 'io', 'inkomen', 'lasten']
            : ['start', 'bedrag', 'periode', 'rente', 'vorm', 'io', 'inkomen', 'lasten'];
    }

    public function stapSleutel(): string
    {
        $steps = $this->steps();

        return $steps[$this->step] ?? $steps[0];
    }

    /**
     * De maandkosten; zonder invoer de standaardposten uit het ontwerp
     * (de tweede en derde suggestie).
     *
     * @return list<array{id:int,label:string,amount:float}>
     */
    public function kostenLijst(): array
    {
        if ($this->costs !== null) {
            return $this->costs;
        }

        /** @var list<array{0:string,1:int}> $namen */
        $namen = Translations::voor($this->lang)['costNames'];

        return [
            ['id' => 1, 'label' => $namen[1][0], 'amount' => (float)$namen[1][1]],
            ['id' => 2, 'label' => $namen[2][0], 'amount' => (float)$namen[2][1]],
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'lang' => $this->lang, 'theme' => $this->theme, 'view' => $this->view,
            'step' => $this->step, 'mode' => $this->mode, 'pay' => $this->pay,
            'plan' => $this->plan, 'path' => $this->path,
            'price' => $this->price, 'own' => $this->own,
            'curBalance' => $this->curBalance, 'curRate' => $this->curRate,
            'curRemaining' => $this->curRemaining, 'curForm' => $this->curForm,
            'preTwentyThirteen' => $this->preTwentyThirteen,
            'termY' => $this->termY, 'fixedY' => $this->fixedY,
            'rate' => $this->rate, 'form' => $this->form,
            'io' => $this->io, 'ioRate' => $this->ioRate,
            'income' => $this->income, 'income2' => $this->income2, 'woz' => $this->woz,
            'costs' => $this->costs, 'parts' => $this->parts,
            'loan' => $this->loan, 'lender' => $this->lender, 'nextId' => $this->nextId,
        ];
    }

    /** @param array<string, mixed> $in */
    private static function str(array $in, string $sleutel): ?string
    {
        $waarde = $in[$sleutel] ?? null;

        return is_string($waarde) ? $waarde : null;
    }

    /** @param list<string> $toegestaan */
    private static function keuze(?string $waarde, array $toegestaan, string $standaard): string
    {
        return in_array($waarde, $toegestaan, true) ? (string)$waarde : $standaard;
    }

    /**
     * Getallen komen binnen zoals de gebruiker ze typt: "265.000", "2,10", "€ 4.000".
     *
     * @param array<string, mixed> $in
     */
    private static function getal(array $in, string $sleutel, float $standaard, float $min, float $max): float
    {
        if (!isset($in[$sleutel]) || $in[$sleutel] === '' || is_array($in[$sleutel])) {
            return $standaard;
        }

        return max($min, min($max, self::num((string)$in[$sleutel])));
    }

    /** De num()-helper uit het ontwerp: duizendpunten weg, komma wordt punt. */
    public static function num(string $ruw): float
    {
        $t = preg_replace('/[^0-9,.-]/', '', $ruw) ?? '';
        $t = preg_replace('/\.(?=\d{3}\b)/', '', $t) ?? '';
        $t = str_replace(',', '.', $t);

        return is_numeric($t) ? (float)$t : 0.0;
    }

    /**
     * @param array<string, mixed> $in
     * @return list<array{id:int,label:string,amount:float}>|null
     */
    private static function kosten(array $in): ?array
    {
        if (!isset($in['costs']) || !is_array($in['costs'])) {
            return null;
        }

        $uit = [];
        foreach (array_values($in['costs']) as $i => $rij) {
            if (!is_array($rij)) {
                continue;
            }
            $uit[] = [
                'id'     => (int)($rij['id'] ?? $i + 1),
                'label'  => trim((string)($rij['label'] ?? '')),
                'amount' => max(0.0, self::num((string)($rij['amount'] ?? '0'))),
            ];
        }

        return $uit;
    }

    /**
     * @param array<string, mixed> $in
     * @return list<array<string, mixed>>|null
     */
    private static function delen(array $in): ?array
    {
        if (!isset($in['parts']) || !is_array($in['parts'])) {
            return null;
        }

        $uit = [];
        foreach (array_values($in['parts']) as $i => $rij) {
            if (!is_array($rij)) {
                continue;
            }
            $uit[] = [
                'id'         => (int)($rij['id'] ?? $i),
                'form'       => self::keuze(isset($rij['form']) ? (string)$rij['form'] : null, Constants::FORMS, 'ann'),
                'sum'        => max(0.0, self::num((string)($rij['sum'] ?? '0'))),
                'rate'       => max(0.0, min(25.0, self::num((string)($rij['rate'] ?? '0')))),
                'term'       => (int)max(1, min(40, self::num((string)($rij['term'] ?? '30')))),
                'elapsed'    => (int)max(0, self::num((string)($rij['elapsed'] ?? '0'))),
                'deductible' => !empty($rij['deductible']),
                'dedMonths'  => isset($rij['dedMonths']) ? (int)self::num((string)$rij['dedMonths']) : 360,
            ];
        }

        return $uit;
    }
}
