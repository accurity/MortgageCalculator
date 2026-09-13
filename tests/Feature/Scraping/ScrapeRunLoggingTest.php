<?php

declare(strict_types=1);

namespace Tests\Feature\Scraping;

use App\Models\Lender;
use App\Models\ScrapeRun;
use App\Services\Mortgage\Domain\Scraping\ScrapeRunner;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ScrapeRunLoggingTest extends TestCase
{
    use RefreshDatabase;

    private const UA = 'Mozilla/5.0 (compatible; AccurityRatesBot/1.0; +https://mortgagecalculator.accurity.nl)';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FixedPeriodSeeder::class);
        $this->seed(RiskClassSeeder::class);
    }

    private function fixture(string $naam): string
    {
        return file_get_contents(__DIR__ . '/../../fixtures/scraping/' . $naam);
    }

    private function bron(Lender $lender): void
    {
        $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);
    }

    public function test_geslaagde_run_wordt_gelogd_zonder_ruwe_respons(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $lender = Lender::factory()->create();
        $this->bron($lender);

        (new ScrapeRunner(self::UA))->run($lender);

        $run = ScrapeRun::query()->where('lender_id', $lender->id)->firstOrFail();
        $this->assertSame('ok', $run->status);
        $this->assertSame(3, $run->rate_count);
        $this->assertFalse($run->is_dry_run);
        $this->assertNull($run->raw_response);
        $this->assertGreaterThanOrEqual(0, $run->duration_ms);
    }

    public function test_mislukte_run_bewaart_de_ruwe_respons(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-breekt-halverwege-af.html')),
        ]);
        $lender = Lender::factory()->create();
        $this->bron($lender);

        (new ScrapeRunner(self::UA))->run($lender);

        $run = ScrapeRun::query()->where('lender_id', $lender->id)->firstOrFail();
        $this->assertSame('failed', $run->status);
        $this->assertSame(0, $run->rate_count);
        $this->assertNotNull($run->raw_response);
        $this->assertStringContainsString('onbekend', $run->raw_response);
    }

    public function test_dry_run_wordt_gemarkeerd_als_dry_run(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $lender = Lender::factory()->create();
        $this->bron($lender);

        (new ScrapeRunner(self::UA))->run($lender, dryRun: true);

        $run = ScrapeRun::query()->where('lender_id', $lender->id)->firstOrFail();
        $this->assertTrue($run->is_dry_run);
    }

    public function test_geweigerde_run_zonder_toestemming_wordt_ook_gelogd(): void
    {
        $lender = Lender::factory()->create();
        $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => false,
        ]);

        (new ScrapeRunner(self::UA))->run($lender);

        $this->assertSame(1, ScrapeRun::query()->where('lender_id', $lender->id)->count());
    }
}
