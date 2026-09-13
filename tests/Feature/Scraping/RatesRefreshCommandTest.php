<?php

declare(strict_types=1);

namespace Tests\Feature\Scraping;

use App\Models\Lender;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class RatesRefreshCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FixedPeriodSeeder::class);
        $this->seed(RiskClassSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    private function fixture(string $naam): string
    {
        return file_get_contents(__DIR__ . '/../../fixtures/scraping/' . $naam);
    }

    public function test_ververst_alle_toegestane_verstrekkers(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $lender = Lender::factory()->create();
        $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);

        $this->artisan('rates:refresh')->assertExitCode(0);

        $this->assertSame(1, $lender->rateSets()->where('is_current', true)->count());
    }

    public function test_dry_run_optie_slaat_niets_op(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $lender = Lender::factory()->create();
        $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);

        $this->artisan('rates:refresh --dry-run')->assertExitCode(0);

        $this->assertSame(0, $lender->rateSets()->count());
    }

    public function test_lender_optie_beperkt_tot_een_verstrekker(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $doel = Lender::factory()->create(['slug' => 'doel-merk']);
        $doel->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);
        $ander = Lender::factory()->create(['slug' => 'ander-merk']);
        $ander->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);

        $this->artisan('rates:refresh --lender=doel-merk')->assertExitCode(0);

        $this->assertSame(1, $doel->rateSets()->count());
        $this->assertSame(0, $ander->rateSets()->count());
    }

    public function test_niet_toegestane_verstrekker_geeft_foutcode(): void
    {
        $lender = Lender::factory()->create(['slug' => 'geen-toestemming']);
        $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => false,
        ]);

        $this->artisan('rates:refresh --lender=geen-toestemming')->assertExitCode(1);
    }

    public function test_een_falende_verstrekker_blokkeert_de_andere_niet(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'kapot.nl/tarieven' => Http::response($this->fixture('tarieven-breekt-halverwege-af.html')),
            'goed.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $kapot = Lender::factory()->create(['slug' => 'kapot-merk']);
        $kapot->rateSource()->create([
            'url' => 'https://kapot.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);
        $goed = Lender::factory()->create(['slug' => 'goed-merk']);
        $goed->rateSource()->create([
            'url' => 'https://goed.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);

        $this->artisan('rates:refresh')->assertExitCode(1);

        $this->assertSame(0, $kapot->rateSets()->count());
        $this->assertSame(1, $goed->rateSets()->where('is_current', true)->count());
    }
}
