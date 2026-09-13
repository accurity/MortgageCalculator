<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain;

use App\Models\TaxYear;

/**
 * Fiscale parameters voor box 1 / eigen woning, per jaar.
 *
 * De waarden komen uit de tabel `tax_years`, beheerd via /admin/tax-years.
 * Controleer ze altijd bij de Belastingdienst voordat je op de uitkomst
 * vertrouwt. Voor jaren die niet in de tabel staan wordt het dichtstbijzijnde
 * bekende jaar gebruikt en geldt de uitkomst als schatting.
 */
final class TaxRules
{
    /** @var array<int, array{schijven: list<array{tot: float|null, tarief: float}>, max_aftrektarief: float, ewf_percentage: float, ewf_grens: float, hillen: float}>|null */
    private static ?array $jaren = null;

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
        $alle = self::alleJaren();
        if ($alle === []) {
            throw new \RuntimeException('Geen belastingjaren beschikbaar; vul de admin of draai de seeder.');
        }

        $bekend = array_keys($alle);
        $isSchatting = !in_array($jaar, $bekend, true);

        $gekozen = $jaar;
        if ($isSchatting) {
            $gekozen = $jaar < min($bekend) ? min($bekend) : max($bekend);
        }
        $cfg = $alle[$gekozen];

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

    /**
     * Eén keer per request opgehaald en in het geheugen bewaard: de
     * jaaraggregatie roept voorJaar() aan voor elk kalenderjaar in het
     * aflossingsschema (tot dertig keer per berekening).
     *
     * @return array<int, array{schijven: list<array{tot: float|null, tarief: float}>, max_aftrektarief: float, ewf_percentage: float, ewf_grens: float, hillen: float}>
     */
    private static function alleJaren(): array
    {
        if (self::$jaren === null) {
            self::$jaren = TaxYear::query()->orderBy('jaar')->get()
                ->mapWithKeys(static fn (TaxYear $j) => [$j->jaar => [
                    'schijven' => $j->schijven,
                    'max_aftrektarief' => $j->max_aftrektarief,
                    'ewf_percentage' => $j->ewf_percentage,
                    'ewf_grens' => $j->ewf_grens,
                    'hillen' => $j->hillen_aandeel,
                ]])
                ->all();
        }

        return self::$jaren;
    }

    /** Wist de in-memory cache; nodig na een wijziging in dezelfde requestcyclus (tests, seeders). */
    public static function verversCache(): void
    {
        self::$jaren = null;
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
