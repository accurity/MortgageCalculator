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

            $lender = $lenders->get($slug);
            if ($lender === null) {
                $fouten[] = "Regel $rijnr: onbekende verstrekker \"$slug\".";
                continue;
            }

            $resolved = RateRowResolver::resolve(
                (string)($velden[$index['periode']] ?? ''),
                (string)($velden[$index['klasse']] ?? ''),
                (string)($velden[$index['nhg']] ?? ''),
                (string)($velden[$index['rente']] ?? ''),
                $periodes,
                $klassenByCode,
                $nhgKlasse,
            );
            if (is_string($resolved)) {
                $fouten[] = "Regel $rijnr: $resolved";
                continue;
            }

            $sleutel = "{$lender->id}-{$resolved['fixed_period_id']}-{$resolved['risk_class_id']}";
            if (isset($gezien[$sleutel])) {
                $fouten[] = "Regel $rijnr: dubbele combinatie van verstrekker, periode en klasse (ook op regel {$gezien[$sleutel]}).";
                continue;
            }
            $gezien[$sleutel] = $rijnr;

            $rijen[] = [
                'lender_id' => $lender->id,
                'lender_naam' => $lender->name,
                ...$resolved,
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
