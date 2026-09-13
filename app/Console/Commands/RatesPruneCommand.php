<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ScrapeRun;
use Illuminate\Console\Command;

/** Ruimt scrape-logregels ouder dan 90 dagen op. Tariefsets blijven staan. */
final class RatesPruneCommand extends Command
{
    private const BEWAARTERMIJN_DAGEN = 90;

    protected $signature = 'rates:prune';

    protected $description = 'Verwijdert scrape-logregels ouder dan 90 dagen';

    public function handle(): int
    {
        $aantal = ScrapeRun::query()
            ->where('created_at', '<', now()->subDays(self::BEWAARTERMIJN_DAGEN))
            ->delete();

        $this->info("$aantal scrape-logregel(s) ouder dan " . self::BEWAARTERMIJN_DAGEN . ' dagen verwijderd.');

        return self::SUCCESS;
    }
}
