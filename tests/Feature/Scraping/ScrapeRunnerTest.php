<?php

declare(strict_types=1);

namespace Tests\Feature\Scraping;

use App\Models\Lender;
use App\Models\RateSource;
use App\Services\Mortgage\Domain\Scraping\ScrapeRunner;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ScrapeRunnerTest extends TestCase
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

    private function bron(Lender $lender, bool $toegestaan = true): RateSource
    {
        return $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => $toegestaan,
        ]);
    }

    public function test_geslaagde_run_maakt_een_nieuwe_actuele_set(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $lender = Lender::factory()->create();
        $oudeSet = $lender->rateSets()->create(['is_current' => true]);
        $this->bron($lender);

        $resultaat = (new ScrapeRunner(self::UA))->run($lender);

        $this->assertTrue($resultaat->success);
        $this->assertFalse($oudeSet->refresh()->is_current);
        $nieuweSet = $lender->rateSets()->where('is_current', true)->firstOrFail();
        $this->assertCount(3, $nieuweSet->rates);
    }

    public function test_dry_run_slaat_niets_op(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $lender = Lender::factory()->create();
        $this->bron($lender);

        $resultaat = (new ScrapeRunner(self::UA))->run($lender, dryRun: true);

        $this->assertTrue($resultaat->success);
        $this->assertSame(0, $lender->rateSets()->count());
    }

    public function test_mislukte_run_laat_de_vorige_set_ongewijzigd_actueel(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-breekt-halverwege-af.html')),
        ]);
        $lender = Lender::factory()->create();
        $oudeSet = $lender->rateSets()->create(['is_current' => true]);
        $this->bron($lender);

        $resultaat = (new ScrapeRunner(self::UA))->run($lender);

        $this->assertFalse($resultaat->success);
        $this->assertTrue($oudeSet->refresh()->is_current);
        $this->assertSame(1, $lender->rateSets()->count());
    }

    public function test_zonder_toestemming_wordt_niet_gescraped(): void
    {
        Http::fake();
        $lender = Lender::factory()->create();
        $this->bron($lender, toegestaan: false);

        $resultaat = (new ScrapeRunner(self::UA))->run($lender);

        $this->assertFalse($resultaat->success);
        Http::assertNothingSent();
    }

    public function test_robots_txt_verbod_blokkeert_de_run(): void
    {
        Http::fake(['*/robots.txt' => Http::response("User-agent: *\nDisallow: /\n")]);
        $lender = Lender::factory()->create();
        $this->bron($lender);

        $resultaat = (new ScrapeRunner(self::UA))->run($lender);

        $this->assertFalse($resultaat->success);
        $this->assertStringContainsString('robots.txt', $resultaat->message);
    }

    public function test_falende_verstrekker_blokkeert_andere_verstrekkers_niet(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-breekt-halverwege-af.html')),
            'ok.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $mislukt = Lender::factory()->create(['slug' => 'mislukt-merk']);
        $this->bron($mislukt);
        $ok = Lender::factory()->create(['slug' => 'goed-merk']);
        $ok->rateSource()->create([
            'url' => 'https://ok.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);

        $runner = new ScrapeRunner(self::UA);
        $resultaatMislukt = $runner->run($mislukt);
        $resultaatOk = $runner->run($ok);

        $this->assertFalse($resultaatMislukt->success);
        $this->assertTrue($resultaatOk->success);
    }
}
