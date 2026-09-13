<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain\Scraping;

use App\Models\FixedPeriod;
use App\Models\RiskClass;
use App\Services\Mortgage\Domain\RateRowResolver;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\CssSelector\Exception\ExceptionInterface as CssSelectorException;

/**
 * Zet één HTML-tabel om naar tarief-rijen aan de hand van een kolomtoewijzing
 * (rol per kolom: periode, klasse, nhg, rente of negeren). Alles-of-niets:
 * bij één ongeldige of dubbele rij levert parseer() alleen een foutmelding op.
 */
final class HtmlTableScraper
{
    /** @param list<string> $columnMap rol per kolom, op volgorde */
    public static function parseer(string $html, string $tableSelector, array $columnMap): ScrapeResult
    {
        try {
            $xpath = (new CssSelectorConverter())->toXPath($tableSelector);
        } catch (CssSelectorException $e) {
            return ScrapeResult::mislukt("Ongeldige CSS-selector \"$tableSelector\": " . $e->getMessage());
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();

        $domXpath = new \DOMXPath($dom);
        $tabellen = $domXpath->query($xpath);
        if ($tabellen === false || $tabellen->length === 0) {
            return ScrapeResult::mislukt("Geen tabel gevonden voor selector \"$tableSelector\".");
        }
        $tabel = $tabellen->item(0);

        $periodes = FixedPeriod::query()->where('active', true)->get()->keyBy('years');
        $klassen = RiskClass::query()->where('active', true)->get();
        $klassenByCode = $klassen->keyBy(static fn (RiskClass $k) => strtolower($k->code));
        $nhgKlasse = $klassen->first(static fn (RiskClass $k) => $k->nhg);

        $rijen = [];
        $fouten = [];
        $gezien = [];
        $rijnr = 0;

        foreach ($domXpath->query('.//tr', $tabel) as $rij) {
            $cellen = $domXpath->query('.//td', $rij);
            if ($cellen->length === 0) {
                continue; // koprij (alleen <th>) of lege rij
            }
            $rijnr++;

            $waarden = [];
            foreach ($cellen as $cel) {
                $waarden[] = trim($cel->textContent);
            }

            $velden = ['periode' => '', 'klasse' => '', 'nhg' => '', 'rente' => ''];
            foreach ($columnMap as $i => $rol) {
                if ($rol === 'negeren' || !array_key_exists($rol, $velden) || !isset($waarden[$i])) {
                    continue;
                }
                $velden[$rol] = $waarden[$i];
            }

            $resolved = RateRowResolver::resolve(
                $velden['periode'],
                $velden['klasse'],
                $velden['nhg'],
                $velden['rente'],
                $periodes,
                $klassenByCode,
                $nhgKlasse,
            );
            if (is_string($resolved)) {
                $fouten[] = "Rij $rijnr: $resolved";
                continue;
            }

            $sleutel = "{$resolved['fixed_period_id']}-{$resolved['risk_class_id']}";
            if (isset($gezien[$sleutel])) {
                $fouten[] = "Rij $rijnr: dubbele combinatie van periode en klasse (ook rij {$gezien[$sleutel]}).";
                continue;
            }
            $gezien[$sleutel] = $rijnr;

            $rijen[] = $resolved;
        }

        if ($fouten !== []) {
            return ScrapeResult::mislukt(implode(' ', $fouten));
        }
        if ($rijen === []) {
            return ScrapeResult::mislukt('Geen geldige rijen gevonden in de tabel.');
        }

        return ScrapeResult::geslaagd($rijen);
    }
}
