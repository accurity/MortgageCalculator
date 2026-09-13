<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain\Scraping;

use App\Models\Lender;
use App\Models\RateSet;
use App\Models\ScrapeRun;
use App\Services\Mortgage\Domain\RateRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Eén scrape-poging voor één verstrekker: robots.txt, ophalen, parsen en -
 * behalve bij een dry-run - alles-of-niets opslaan als een nieuwe actuele
 * tariefset. Een mislukte of onvolledige poging laat de vorige set
 * ongewijzigd actueel; er wordt dan niets geschreven. Elke poging wordt
 * gelogd in scrape_runs (#15); de ruwe respons alleen bij een mislukte of
 * lege run.
 */
final class ScrapeRunner
{
    private const TIMEOUT_SECONDEN = 20;

    public function __construct(private readonly string $userAgent)
    {
    }

    public function run(Lender $lender, bool $dryRun = false): ScrapeResult
    {
        $begin = microtime(true);
        $ruweRespons = null;

        $resultaat = $this->uitvoeren($lender, $dryRun, $ruweRespons);

        ScrapeRun::query()->create([
            'lender_id' => $lender->id,
            'is_dry_run' => $dryRun,
            'duration_ms' => (int)round((microtime(true) - $begin) * 1000),
            'rate_count' => count($resultaat->rijen),
            'status' => $resultaat->success ? 'ok' : 'failed',
            'message' => $resultaat->message,
            'raw_response' => !$resultaat->success ? $ruweRespons : null,
        ]);

        return $resultaat;
    }

    private function uitvoeren(Lender $lender, bool $dryRun, ?string &$ruweRespons): ScrapeResult
    {
        $bron = $lender->rateSource;
        if ($bron === null || !$bron->scraping_allowed) {
            return ScrapeResult::mislukt('Scrapen is niet toegestaan, of er is geen bron ingesteld voor deze verstrekker.');
        }

        if (!RobotsCheck::toegestaan($bron->url, $this->userAgent)) {
            return ScrapeResult::mislukt('robots.txt verbiedt dit pad voor onze User-Agent.');
        }

        try {
            $response = Http::withUserAgent($this->userAgent)->timeout(self::TIMEOUT_SECONDEN)->get($bron->url);
        } catch (\Throwable $e) {
            return ScrapeResult::mislukt('Kon de pagina niet ophalen: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            $ruweRespons = $response->body();

            return ScrapeResult::mislukt('HTTP ' . $response->status() . ' bij het ophalen van de pagina.');
        }

        $ruweRespons = $response->body();
        $resultaat = HtmlTableScraper::parseer($response->body(), $bron->table_selector, $bron->column_map);
        if (!$resultaat->success || $dryRun) {
            return $resultaat;
        }

        DB::transaction(function () use ($lender, $resultaat): void {
            RateSet::query()->where('lender_id', $lender->id)->update(['is_current' => false]);
            $set = RateSet::query()->create([
                'lender_id' => $lender->id,
                'is_current' => true,
                'note' => 'Scraper',
            ]);
            foreach ($resultaat->rijen as $rij) {
                $set->rates()->create([
                    'fixed_period_id' => $rij['fixed_period_id'],
                    'risk_class_id' => $rij['risk_class_id'],
                    'percentage' => $rij['percentage'],
                ]);
            }
        });

        RateRepository::verversCache();

        return $resultaat;
    }
}
