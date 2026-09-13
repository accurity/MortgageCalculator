<?php

declare(strict_types=1);

namespace Tests\Feature\Scraping;

use App\Services\Mortgage\Domain\Scraping\HtmlTableScraper;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HtmlTableScraperTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_geldige_tabel_levert_rijen_op(): void
    {
        $resultaat = HtmlTableScraper::parseer(
            $this->fixture('tarieven-geldig.html'),
            '#tarieven',
            ['periode', 'klasse', 'nhg', 'rente']
        );

        $this->assertTrue($resultaat->success);
        $this->assertCount(3, $resultaat->rijen);
        $this->assertSame(3.75, $resultaat->rijen[0]['percentage']);
    }

    public function test_nhg_kolom_overschrijft_klasse(): void
    {
        $resultaat = HtmlTableScraper::parseer(
            $this->fixture('tarieven-geldig.html'),
            '#tarieven',
            ['periode', 'klasse', 'nhg', 'rente']
        );

        $this->assertTrue($resultaat->success);
        $nhgRij = collect($resultaat->rijen)->firstWhere('percentage', 3.10);
        $this->assertSame('NHG', $nhgRij['klasse_naam']);
    }

    public function test_rij_die_halverwege_afbreekt_blokkeert_de_hele_tabel(): void
    {
        $resultaat = HtmlTableScraper::parseer(
            $this->fixture('tarieven-breekt-halverwege-af.html'),
            '#tarieven',
            ['periode', 'klasse', 'nhg', 'rente']
        );

        $this->assertFalse($resultaat->success);
        $this->assertSame([], $resultaat->rijen);
        $this->assertStringContainsString('onbekende of inactieve periode', $resultaat->message);
    }

    public function test_onbekende_selector_levert_een_foutmelding_op(): void
    {
        $resultaat = HtmlTableScraper::parseer(
            $this->fixture('tarieven-geldig.html'),
            '#bestaat-niet',
            ['periode', 'klasse', 'nhg', 'rente']
        );

        $this->assertFalse($resultaat->success);
        $this->assertStringContainsString('Geen tabel gevonden', $resultaat->message);
    }

    public function test_dubbele_combinatie_blokkeert_de_hele_tabel(): void
    {
        $html = '<table id="t"><tr><td>10</td><td>ltv90</td><td>nee</td><td>3,75</td></tr>'
            . '<tr><td>10</td><td>ltv90</td><td>nee</td><td>3,80</td></tr></table>';

        $resultaat = HtmlTableScraper::parseer($html, '#t', ['periode', 'klasse', 'nhg', 'rente']);

        $this->assertFalse($resultaat->success);
        $this->assertStringContainsString('dubbele combinatie', $resultaat->message);
    }
}
