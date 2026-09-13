<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain;

use App\Models\FixedPeriod;
use App\Models\RiskClass;
use Illuminate\Support\Collection;

/**
 * Herleidt één ruwe (periode, klasse, nhg, rente)-combinatie naar een
 * geldige periode+tariefklasse+percentage, of een foutmelding. Gedeeld
 * tussen de CSV-import (#9) en de scraper (#14) - dezelfde regels, twee
 * bronnen.
 */
final class RateRowResolver
{
    private const NHG_WAARDEN = ['1', 'ja', 'j', 'true', 'yes'];
    private const MAX_RENTE = 15.0;

    /**
     * @param Collection<int, FixedPeriod> $periodes jaren => periode, alleen actieve
     * @param Collection<string, RiskClass> $klassenByCode code (kleine letters) => klasse, alleen actieve, geen NHG
     * @return array{fixed_period_id: int, periode_jaren: int, risk_class_id: int, klasse_naam: string, percentage: float}|string de rij, of een foutmelding
     */
    public static function resolve(
        string $periodeRuw,
        string $klasseRuw,
        string $nhgRuw,
        string $renteRuw,
        Collection $periodes,
        Collection $klassenByCode,
        ?RiskClass $nhgKlasse,
    ): array|string {
        $periodeRuw = trim($periodeRuw);
        $klasseRuw = strtolower(trim($klasseRuw));
        $nhgRuw = strtolower(trim($nhgRuw));
        $renteRuw = trim($renteRuw);

        $periode = ctype_digit($periodeRuw) ? $periodes->get((int)$periodeRuw) : null;
        if ($periode === null) {
            return "onbekende of inactieve periode \"$periodeRuw\".";
        }

        $isNhg = in_array($nhgRuw, self::NHG_WAARDEN, true);
        if ($isNhg) {
            if ($nhgKlasse === null) {
                return 'er is geen actieve NHG-klasse ingesteld.';
            }
            $klasse = $nhgKlasse;
        } else {
            $klasse = $klassenByCode->get($klasseRuw);
            if ($klasse === null || $klasse->nhg) {
                return "onbekende of inactieve tariefklasse \"$klasseRuw\".";
            }
        }

        $renteGenormaliseerd = str_replace(',', '.', $renteRuw);
        if (!is_numeric($renteGenormaliseerd)) {
            return "rente \"$renteRuw\" is geen getal.";
        }
        $rente = (float)$renteGenormaliseerd;
        if ($rente < 0 || $rente > self::MAX_RENTE) {
            return "rente \"$renteRuw\" ligt buiten de redelijke grens van 0 tot " . self::MAX_RENTE . '%.';
        }

        return [
            'fixed_period_id' => $periode->id,
            'periode_jaren' => $periode->years,
            'risk_class_id' => $klasse->id,
            'klasse_naam' => $klasse->name,
            'percentage' => $rente,
        ];
    }
}
