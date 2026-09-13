<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lender;
use App\Models\Setting;
use App\Services\Mortgage\Domain\Scraping\ScrapeRunner;
use Illuminate\Console\Command;

/**
 * Ververst de tarieven van verstrekkers met een toegestane scrape-bron.
 * Alles-of-niets per verstrekker: een mislukte of onvolledige run laat de
 * vorige tariefset ongewijzigd actueel. Eén falende verstrekker blokkeert
 * de andere niet.
 */
final class RatesRefreshCommand extends Command
{
    protected $signature = 'rates:refresh {--lender= : Beperk tot deze verstrekker (slug)} {--dry-run : Toon het resultaat zonder iets op te slaan}';

    protected $description = 'Ververst de tarieven van verstrekkers via hun scrape-bron';

    public function handle(): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $userAgent = (string)Setting::get(
            'scraper_user_agent',
            'Mozilla/5.0 (compatible; AccurityRatesBot/1.0; +https://mortgagecalculator.accurity.nl)'
        );
        $runner = new ScrapeRunner($userAgent);

        if ($slug = $this->option('lender')) {
            $lender = Lender::query()->where('slug', $slug)->first();
            if ($lender === null) {
                $this->error("Onbekende verstrekker \"$slug\".");

                return self::FAILURE;
            }
            if ($lender->rateSource === null || !$lender->rateSource->scraping_allowed) {
                $this->error("Scrapen is niet toegestaan voor \"$slug\".");

                return self::FAILURE;
            }
            $lenders = collect([$lender]);
        } else {
            $lenders = Lender::query()
                ->whereHas('rateSource', static fn ($q) => $q->where('scraping_allowed', true))
                ->get();
        }

        if ($lenders->isEmpty()) {
            $this->warn('Geen verstrekkers met een toegestane scrape-bron gevonden.');

            return self::SUCCESS;
        }

        $mislukt = 0;
        foreach ($lenders as $lender) {
            $this->line("Verstrekker: {$lender->name}");

            try {
                $resultaat = $runner->run($lender, $dryRun);
            } catch (\Throwable $e) {
                $this->error('  Onverwachte fout: ' . $e->getMessage());
                $mislukt++;
                continue;
            }

            if ($resultaat->success) {
                $suffix = $dryRun ? ' (dry-run, niets opgeslagen)' : ' - actueel gemaakt.';
                $this->info('  OK: ' . $resultaat->message . $suffix);
            } else {
                $mislukt++;
                $this->error('  Mislukt: ' . $resultaat->message . ' De vorige tariefset blijft actueel.');
            }
        }

        return $mislukt > 0 ? self::FAILURE : self::SUCCESS;
    }
}
