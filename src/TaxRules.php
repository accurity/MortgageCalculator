<?php

declare(strict_types=1);

namespace Hypotheek;

/**
 * Fiscale parameters voor box 1 / eigen woning, per jaar.
 *
 * LET OP: deze tarieven zijn hier bewust als losstaande configuratie opgenomen
 * zodat ze eenvoudig bijgewerkt kunnen worden. Controleer de waarden altijd bij
 * de Belastingdienst voordat je op de uitkomst vertrouwt. Voor jaren die niet in
 * de tabel staan wordt het dichtstbijzijnde bekende jaar gebruikt.
 */
final class TaxRules
{
    /**
     * Schijven voor belastingplichtigen die de AOW-leeftijd nog niet hebben bereikt.
     * Elke schijf: ['tot' => bovengrens of null, 'tarief' => fractie].
     *
     * @var array<int, array{schijven: list<array{tot: float|null, tarief: float}>, max_aftrektarief: float, ewf_percentage: float, ewf_grens: float, hillen: float}>
     */
    private const JAREN = [
        2024 => [
            'schijven' => [
                ['tot' => 75518.0, 'tarief' => 0.3697],
                ['tot' => null,    'tarief' => 0.4950],
            ],
            'max_aftrektarief' => 0.3697,
            'ewf_percentage'   => 0.0035,
            'ewf_grens'        => 1310000.0,
            'hillen'           => 0.8000,
        ],
        2025 => [
            'schijven' => [
                ['tot' => 38441.0, 'tarief' => 0.3582],
                ['tot' => 76817.0, 'tarief' => 0.3748],
                ['tot' => null,    'tarief' => 0.4950],
            ],
            'max_aftrektarief' => 0.3748,
            'ewf_percentage'   => 0.0035,
            'ewf_grens'        => 1330000.0,
            'hillen'           => 0.7667,
        ],
        2026 => [
            'schijven' => [
                ['tot' => 38883.0, 'tarief' => 0.3570],
                ['tot' => 79137.0, 'tarief' => 0.3756],
                ['tot' => null,    'tarief' => 0.4950],
            ],
            'max_aftrektarief' => 0.3756,
            'ewf_percentage'   => 0.0035,
            'ewf_grens'        => 1350000.0,
            'hillen'           => 0.7333,
        ],
    ];

    /**
     * @param list<array{tot: float|null, tarief: float}> $schijven
     */
    private function __construct(
        public readonly int $jaar,
        public readonly array $schijven,
        public readonly float $maxAftrektarief,
        public readonly float $ewfPercentage,
        public readonly float $ewfGrens,
        /** Aandeel van de Hillen-aftrek dat in dit jaar nog geldt (0..1). */
        public readonly float $hillenAandeel,
        public readonly bool $isSchatting,
    ) {
    }

    public static function voorJaar(int $jaar): self
    {
        $bekend = array_keys(self::JAREN);
        $isSchatting = !in_array($jaar, $bekend, true);

        $gekozen = $jaar;
        if ($isSchatting) {
            $gekozen = $jaar < min($bekend) ? min($bekend) : max($bekend);
        }
        $cfg = self::JAREN[$gekozen];

        // Wet Hillen wordt in gelijke stappen afgebouwd tot 0 in 2048.
        $hillen = $cfg['hillen'];
        if ($isSchatting && $jaar > max($bekend)) {
            $hillen = max(0.0, $cfg['hillen'] - 0.0333 * ($jaar - max($bekend)));
        }

        return new self(
            jaar: $jaar,
            schijven: $cfg['schijven'],
            maxAftrektarief: $cfg['max_aftrektarief'],
            ewfPercentage: $cfg['ewf_percentage'],
            ewfGrens: $cfg['ewf_grens'],
            hillenAandeel: $hillen,
            isSchatting: $isSchatting,
        );
    }

    /** Inkomstenbelasting box 1 over een belastbaar inkomen (zonder heffingskortingen). */
    public function belasting(float $inkomen): float
    {
        $inkomen = max(0.0, $inkomen);
        $belasting = 0.0;
        $ondergrens = 0.0;

        foreach ($this->schijven as $schijf) {
            $bovengrens = $schijf['tot'] ?? INF;
            $deel = max(0.0, min($inkomen, $bovengrens) - $ondergrens);
            $belasting += $deel * $schijf['tarief'];
            $ondergrens = $bovengrens;
            if ($inkomen <= $bovengrens) {
                break;
            }
        }

        return $belasting;
    }

    /** Marginaal tarief bij een gegeven inkomen. */
    public function marginaalTarief(float $inkomen): float
    {
        $ondergrens = 0.0;
        foreach ($this->schijven as $schijf) {
            $bovengrens = $schijf['tot'] ?? INF;
            if ($inkomen <= $bovengrens) {
                return $schijf['tarief'];
            }
            $ondergrens = $bovengrens;
        }

        return $this->schijven[count($this->schijven) - 1]['tarief'];
    }

    /** Ondergrens van de hoogste schijf (waarop de tariefsaanpassing aangrijpt). */
    public function topschijfGrens(): float
    {
        $aantal = count($this->schijven);
        if ($aantal < 2) {
            return INF;
        }

        return (float)$this->schijven[$aantal - 2]['tot'];
    }

    public function topTarief(): float
    {
        return $this->schijven[count($this->schijven) - 1]['tarief'];
    }
}
