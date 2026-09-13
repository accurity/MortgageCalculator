<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TaxYear;
use App\Services\Mortgage\Domain\TaxCalculator;
use App\Services\Mortgage\Domain\TaxRules;
use Database\Seeders\TaxYearSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zelfde gevallen als het oorspronkelijke tests/run.php, nu tegen de
 * database-gedreven TaxRules (voorheen een hardgecodeerde constante).
 */
final class TaxCalculationTest extends TestCase
{
    use RefreshDatabase;

    private function bijna(float $a, float $b, float $marge = 0.01): bool
    {
        return abs($a - $b) <= $marge;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxYearSeeder::class);
        TaxRules::verversCache();
    }

    public function test_belastingberekening(): void
    {
        $tax = new TaxCalculator(400000.0);
        $r = $tax->bereken(2026, 70000.0, 12000.0);

        $this->assertTrue($this->bijna($r->eigenwoningforfait, 1400.0));
        $this->assertTrue($this->bijna($r->aftrekpost, 10600.0));
        $this->assertGreaterThan(0, $r->voordeelJaar);
        $this->assertTrue(
            $this->bijna($r->effectiefVoordeelTarief, 0.3756, 0.01),
            (string)round($r->effectiefVoordeelTarief, 4)
        );

        $regels = TaxRules::voorJaar(2026);
        $hoog = $tax->bereken(2026, 120000.0, 20000.0);
        $maxTarief = $regels->maxAftrektarief;
        $this->assertLessThanOrEqual(
            $maxTarief + 0.0001,
            $hoog->effectiefVoordeelTarief,
            (string)round($hoog->effectiefVoordeelTarief, 4)
        );

        $laag = $tax->bereken(2026, 30000.0, 500.0);
        $this->assertGreaterThan(0.0, $laag->hillenAftrek);
    }

    public function test_jaar_zonder_gegevens_gebruikt_laatst_bekende_jaar_en_markeert_schatting(): void
    {
        $regels2026 = TaxRules::voorJaar(2026);
        $regels2030 = TaxRules::voorJaar(2030);

        $this->assertFalse($regels2026->isSchatting);
        $this->assertTrue($regels2030->isSchatting);
        $this->assertSame($regels2026->maxAftrektarief, $regels2030->maxAftrektarief);
        $this->assertSame($regels2026->ewfGrens, $regels2030->ewfGrens);
        // Wet Hillen bouwt af na het laatst bekende jaar.
        $this->assertLessThan($regels2026->hillenAandeel, $regels2030->hillenAandeel);
    }

    public function test_ontbrekend_jaar_voor_het_vroegste_bekende_jaar_gebruikt_dat_jaar(): void
    {
        $regels = TaxRules::voorJaar(2020);

        $this->assertTrue($regels->isSchatting);
        $this->assertSame(2024, TaxYear::query()->min('jaar'));
    }
}
