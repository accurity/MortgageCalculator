<?php

declare(strict_types=1);

namespace Hypotheek;

/**
 * Eén maandregel uit een aflossingsschema.
 */
final class MonthRow
{
    public function __construct(
        public readonly int $index,          // 1-gebaseerd, vanaf start berekening
        public readonly int $jaar,
        public readonly int $maand,          // 1..12
        public readonly float $beginschuld,
        public readonly float $rente,
        public readonly float $aflossing,
        public readonly float $eindschuld,
        public readonly float $aftrekbareRente,
    ) {
    }

    /** Bruto maandlast van dit leningdeel: rente + aflossing. */
    public function bruto(): float
    {
        return $this->rente + $this->aflossing;
    }
}

/**
 * Het complete aflossingsschema van één leningdeel.
 */
final class Schedule
{
    /**
     * @param list<MonthRow> $rows
     * @param float $slotsom Restschuld die aan het einde van de looptijd in één keer
     *                       moet worden voldaan (alleen bij aflossingsvrij).
     */
    public function __construct(
        public readonly LoanPart $part,
        public readonly array $rows,
        public readonly float $slotsom,
        public readonly float $annuiteitBedrag,
    ) {
    }

    public function totaleRente(): float
    {
        return array_sum(array_map(static fn (MonthRow $r): float => $r->rente, $this->rows));
    }

    public function totaleAflossing(): float
    {
        return array_sum(array_map(static fn (MonthRow $r): float => $r->aflossing, $this->rows));
    }
}

/**
 * Bouwt aflossingsschema's op maandbasis.
 */
final class Amortization
{
    public function __construct(
        private readonly int $startJaar,
        private readonly int $startMaand, // 1..12
        /** Aantal maanden waarover de rente maximaal aftrekbaar is (30 jaar). */
        private readonly int $aftrekTermijnMaanden = 360,
    ) {
    }

    public function schedule(LoanPart $part): Schedule
    {
        $n = $part->resterendeMaanden();
        $i = $part->maandRente();
        $schuld = $part->beginschuld();

        $annuiteit = 0.0;
        if ($part->type === LoanPart::TYPE_ANNUITAIR && $n > 0) {
            $annuiteit = $i > 0.0
                ? $schuld * $i / (1 - (1 + $i) ** -$n)
                : $schuld / $n;
        }
        $lineaireAflossing = ($part->type === LoanPart::TYPE_LINEAIR && $n > 0) ? $schuld / $n : 0.0;

        $rows = [];
        for ($k = 1; $k <= $n; $k++) {
            $begin = $schuld;
            $rente = $begin * $i;

            $aflossing = match ($part->type) {
                LoanPart::TYPE_ANNUITAIR      => $annuiteit - $rente,
                LoanPart::TYPE_LINEAIR        => $lineaireAflossing,
                LoanPart::TYPE_AFLOSSINGSVRIJ => 0.0,
                default                       => 0.0,
            };

            // Afrondingsverschillen mogen nooit tot een negatieve restschuld leiden.
            $aflossing = max(0.0, min($aflossing, $begin));
            if ($k === $n && $part->type !== LoanPart::TYPE_AFLOSSINGSVRIJ) {
                $aflossing = $begin; // sluit exact af op nul
            }

            $eind = $begin - $aflossing;

            // De renteaftrek geldt maximaal 30 jaar, gerekend vanaf het ontstaan
            // van de schuld - dus inclusief de maanden die al verstreken zijn.
            $globaleMaand = $part->reedsVerstrekenMaanden + $k;
            $aftrekbaar = ($part->renteAftrekbaar && $globaleMaand <= $this->aftrekTermijnMaanden)
                ? $rente
                : 0.0;

            [$jaar, $maand] = $this->kalender($k);
            $rows[] = new MonthRow($k, $jaar, $maand, $begin, $rente, $aflossing, $eind, $aftrekbaar);

            $schuld = $eind;
        }

        $slotsom = $part->type === LoanPart::TYPE_AFLOSSINGSVRIJ ? $schuld : 0.0;

        return new Schedule($part, $rows, $slotsom, $annuiteit);
    }

    /** @return array{0:int,1:int} */
    private function kalender(int $k): array
    {
        $offset = $this->startMaand - 1 + ($k - 1);

        return [$this->startJaar + intdiv($offset, 12), ($offset % 12) + 1];
    }
}
