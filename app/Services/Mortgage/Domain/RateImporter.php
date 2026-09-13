<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain;

use App\Models\FixedPeriod;
use App\Models\Lender;
use App\Models\RiskClass;

/**
 * Leest een tarieven-CSV in voor verstrekkers die niet gescraped mogen of
 * kunnen worden (#9). Kolommen: slug, periode, klasse, nhg, rente - in elke
 * volgorde, gezocht op de koptekst. Alles-of-niets: bij één ongeldige regel
 * levert verwerk() alleen foutmeldingen op, geen rijen.
 */
final class RateImporter
{
    private const VERPLICHTE_KOLOMMEN = ['slug', 'periode', 'klasse', 'nhg', 'rente'];
    private const NHG_WAARDEN = ['1', 'ja', 'j', 'true', 'yes'];
    private const MAX_RENTE = 15.0;

    public static function verwerk(string $inhoud): RateImportResult
    {
        $regels = preg_split('/\r\n|\r|\n/', trim($inhoud));
        $regels = array_values(array_filter($regels, static fn (string $r): bool => trim($r) !== ''));

        if ($regels === []) {
            return new RateImportResult([], ['Het bestand is leeg.']);
        }

        $index = self::kolomIndex(array_shift($regels));
        if (is_array($index) === false) {
            return new RateImportResult([], [$index]);
        }

        $lenders = Lender::query()->get()->keyBy(static fn (Lender $l) => strtolower($l->slug));
        $periodes = FixedPeriod::query()->where('active', true)->get()->keyBy('years');
        $klassen = RiskClass::query()->where('active', true)->get();
        $klassenByCode = $klassen->keyBy(static fn (RiskClass $k) => strtolower($k->code));
        $nhgKlasse = $klassen->first(static fn (RiskClass $k) => $k->nhg);

        $fouten = [];
        $rijen = [];
        $gezien = [];

        foreach ($regels as $i => $regel) {
            $rijnr = $i + 2; // regel 1 is de kop
            $velden = str_getcsv($regel);

            $slug = strtolower(trim((string)($velden[$index['slug']] ?? '')));
            $periodeRuw = trim((string)($velden[$index['periode']] ?? ''));
            $klasseRuw = strtolower(trim((string)($velden[$index['klasse']] ?? '')));
            $nhgRuw = strtolower(trim((string)($velden[$index['nhg']] ?? '')));
            $renteRuw = trim((string)($velden[$index['rente']] ?? ''));

            $lender = $lenders->get($slug);
            if ($lender === null) {
                $fouten[] = "Regel $rijnr: onbekende verstrekker \"$slug\".";
                continue;
            }

            $periode = ctype_digit($periodeRuw) ? $periodes->get((int)$periodeRuw) : null;
            if ($periode === null) {
                $fouten[] = "Regel $rijnr: onbekende of inactieve periode \"$periodeRuw\".";
                continue;
            }

            $isNhg = in_array($nhgRuw, self::NHG_WAARDEN, true);
            if ($isNhg) {
                if ($nhgKlasse === null) {
                    $fouten[] = "Regel $rijnr: er is geen actieve NHG-klasse ingesteld.";
                    continue;
                }
                $klasse = $nhgKlasse;
            } else {
                $klasse = $klassenByCode->get($klasseRuw);
                if ($klasse === null || $klasse->nhg) {
                    $fouten[] = "Regel $rijnr: onbekende of inactieve tariefklasse \"$klasseRuw\".";
                    continue;
                }
            }

            $renteGenormaliseerd = str_replace(',', '.', $renteRuw);
            if (!is_numeric($renteGenormaliseerd)) {
                $fouten[] = "Regel $rijnr: rente \"$renteRuw\" is geen getal.";
                continue;
            }
            $rente = (float)$renteGenormaliseerd;
            if ($rente < 0 || $rente > self::MAX_RENTE) {
                $fouten[] = "Regel $rijnr: rente \"$renteRuw\" ligt buiten de redelijke grens van 0 tot " . self::MAX_RENTE . '%.';
                continue;
            }

            $sleutel = "{$lender->id}-{$periode->id}-{$klasse->id}";
            if (isset($gezien[$sleutel])) {
                $fouten[] = "Regel $rijnr: dubbele combinatie van verstrekker, periode en klasse (ook op regel {$gezien[$sleutel]}).";
                continue;
            }
            $gezien[$sleutel] = $rijnr;

            $rijen[] = [
                'lender_id' => $lender->id,
                'lender_naam' => $lender->name,
                'fixed_period_id' => $periode->id,
                'periode_jaren' => $periode->years,
                'risk_class_id' => $klasse->id,
                'klasse_naam' => $klasse->name,
                'percentage' => $rente,
            ];
        }

        if ($fouten === [] && $rijen === []) {
            $fouten[] = 'Het bestand bevat geen datarijen.';
        }

        return new RateImportResult($fouten === [] ? $rijen : [], $fouten);
    }

    /** @return array<string, int>|string kolomnaam => index, of een foutmelding */
    private static function kolomIndex(string $kopregel): array|string
    {
        $kolommen = array_map(
            static fn (string $k): string => strtolower(trim($k)),
            str_getcsv($kopregel)
        );
        $index = array_flip($kolommen);

        foreach (self::VERPLICHTE_KOLOMMEN as $verplicht) {
            if (!isset($index[$verplicht])) {
                return "Kolom \"$verplicht\" ontbreekt in de kop van het bestand.";
            }
        }

        return $index;
    }
}
