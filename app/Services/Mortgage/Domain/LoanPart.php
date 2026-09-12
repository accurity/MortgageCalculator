<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain;

/**
 * Eén leningdeel van de hypotheek.
 */
final class LoanPart
{
    public const TYPE_ANNUITAIR     = 'annuitair';
    public const TYPE_LINEAIR       = 'lineair';
    public const TYPE_AFLOSSINGSVRIJ = 'aflossingsvrij';

    /** @var array<string,string> */
    public const TYPE_LABELS = [
        self::TYPE_ANNUITAIR      => 'Annuïtair',
        self::TYPE_LINEAIR        => 'Lineair',
        self::TYPE_AFLOSSINGSVRIJ => 'Aflossingsvrij',
    ];

    public function __construct(
        public readonly string $naam,
        public readonly string $type,
        public readonly float $hoofdsom,
        /** Nominale jaarrente in procenten, bijv. 3.85 */
        public readonly float $rentePercentage,
        /** Periode in maanden waarin het leningdeel volledig is afgelost. */
        public readonly int $looptijdMaanden,
        /** Aantal maanden dat dit leningdeel al loopt (0 = nieuw). */
        public readonly int $reedsVerstrekenMaanden = 0,
        /** Telt de rente mee voor de hypotheekrenteaftrek? */
        public readonly bool $renteAftrekbaar = true,
    ) {
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /** Maandrente als factor, bijv. 0.0032083 */
    public function maandRente(): float
    {
        return $this->rentePercentage / 100 / 12;
    }

    /** Resterende looptijd in maanden vanaf het startmoment van de berekening. */
    public function resterendeMaanden(): int
    {
        return max(0, $this->looptijdMaanden - $this->reedsVerstrekenMaanden);
    }

    /**
     * Openstaande schuld op het startmoment van de berekening.
     * Voor een leningdeel dat al loopt wordt de reeds gemaakte aflossing verrekend.
     */
    public function beginschuld(): float
    {
        $verstreken = $this->reedsVerstrekenMaanden;
        if ($verstreken <= 0 || $this->looptijdMaanden <= 0) {
            return $this->hoofdsom;
        }
        if ($verstreken >= $this->looptijdMaanden) {
            return $this->type === self::TYPE_AFLOSSINGSVRIJ ? $this->hoofdsom : 0.0;
        }

        $i = $this->maandRente();

        return match ($this->type) {
            self::TYPE_AFLOSSINGSVRIJ => $this->hoofdsom,
            self::TYPE_LINEAIR => $this->hoofdsom
                - ($this->hoofdsom / $this->looptijdMaanden) * $verstreken,
            // Restschuld annuïteit = contante waarde van de resterende termijnen.
            self::TYPE_ANNUITAIR => $i <= 0.0
                ? $this->hoofdsom - ($this->hoofdsom / $this->looptijdMaanden) * $verstreken
                : $this->hoofdsom
                    * ((1 - (1 + $i) ** -($this->looptijdMaanden - $verstreken))
                        / (1 - (1 + $i) ** -$this->looptijdMaanden)),
            default => $this->hoofdsom,
        };
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data, int $index): self
    {
        $naam = trim((string)($data['naam'] ?? ''));
        if ($naam === '') {
            $naam = 'Leningdeel ' . $index;
        }

        $type = (string)($data['type'] ?? self::TYPE_ANNUITAIR);
        if (!isset(self::TYPE_LABELS[$type])) {
            $type = self::TYPE_ANNUITAIR;
        }

        $looptijdJaren = Input::toFloat($data['looptijd_jaren'] ?? 30);
        $looptijdMaanden = (int)round($looptijdJaren * 12);

        return new self(
            naam: $naam,
            type: $type,
            hoofdsom: max(0.0, Input::toFloat($data['hoofdsom'] ?? 0)),
            rentePercentage: max(0.0, Input::toFloat($data['rente'] ?? 0)),
            looptijdMaanden: max(0, $looptijdMaanden),
            reedsVerstrekenMaanden: max(0, (int)round(Input::toFloat($data['verstreken_maanden'] ?? 0))),
            renteAftrekbaar: !empty($data['aftrekbaar']),
        );
    }

    /** @return list<string> */
    public function validatieFouten(): array
    {
        $fouten = [];
        if ($this->hoofdsom <= 0) {
            $fouten[] = sprintf('%s: hoofdsom moet groter dan 0 zijn.', $this->naam);
        }
        if ($this->rentePercentage < 0 || $this->rentePercentage > 25) {
            $fouten[] = sprintf('%s: rentepercentage moet tussen 0 en 25 liggen.', $this->naam);
        }
        if ($this->looptijdMaanden < 1 || $this->looptijdMaanden > 600) {
            $fouten[] = sprintf('%s: looptijd moet tussen 1 maand en 50 jaar liggen.', $this->naam);
        }
        if ($this->reedsVerstrekenMaanden >= $this->looptijdMaanden
            && $this->type !== self::TYPE_AFLOSSINGSVRIJ) {
            $fouten[] = sprintf('%s: het leningdeel is al volledig afgelost (verstreken ≥ looptijd).', $this->naam);
        }

        return $fouten;
    }
}
