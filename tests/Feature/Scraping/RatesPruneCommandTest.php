<?php

declare(strict_types=1);

namespace Tests\Feature\Scraping;

use App\Models\Lender;
use App\Models\RateSet;
use App\Models\ScrapeRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RatesPruneCommandTest extends TestCase
{
    use RefreshDatabase;

    private function logRegel(Lender $lender, int $ouderdomDagen): ScrapeRun
    {
        $run = $lender->scrapeRuns()->create([
            'is_dry_run' => false,
            'duration_ms' => 10,
            'rate_count' => 1,
            'status' => 'ok',
            'message' => 'test',
        ]);
        $run->forceFill(['created_at' => now()->subDays($ouderdomDagen)])->save();

        return $run;
    }

    public function test_verwijdert_logregels_ouder_dan_90_dagen(): void
    {
        $lender = Lender::factory()->create();
        $oud = $this->logRegel($lender, 91);

        $this->artisan('rates:prune')->assertExitCode(0);

        $this->assertNull(ScrapeRun::find($oud->id));
    }

    public function test_bewaart_logregels_van_90_dagen_of_jonger(): void
    {
        $lender = Lender::factory()->create();
        $recent = $this->logRegel($lender, 5);

        $this->artisan('rates:prune')->assertExitCode(0);

        $this->assertNotNull(ScrapeRun::find($recent->id));
    }

    public function test_raakt_tariefsets_niet_aan(): void
    {
        $lender = Lender::factory()->create();
        $this->logRegel($lender, 200);
        $set = $lender->rateSets()->create(['is_current' => true]);
        $set->forceFill(['created_at' => now()->subDays(200)])->save();

        $this->artisan('rates:prune');

        $this->assertNotNull(RateSet::find($set->id));
    }
}
