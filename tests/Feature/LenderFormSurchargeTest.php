<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lender;
use App\Services\Mortgage\Constants;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TaxYearSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LenderFormSurchargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxYearSeeder::class);
        $this->seed(FixedPeriodSeeder::class);
        $this->seed(RiskClassSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    public function test_zonder_eigen_opslag_geldt_nul_voor_annuitair(): void
    {
        $lender = Lender::factory()->create();

        $this->assertSame(0.0, $lender->vormOpslagen()['ann']);
    }

    public function test_zonder_eigen_opslag_geldt_de_algemene_opslag_voor_aflossingsvrij(): void
    {
        $lender = Lender::factory()->create();

        $this->assertSame(Constants::ioSurcharge(), $lender->vormOpslagen()['av']);
    }

    public function test_eigen_opslag_overschrijft_de_algemene_opslag(): void
    {
        $lender = Lender::factory()->create(['surcharge_av' => 0.9]);

        $this->assertSame(0.9, $lender->vormOpslagen()['av']);
    }

    /** @return array<string, mixed> */
    private function naarFormEnStateVelden(string $vorm): array
    {
        $skip = $this->post('/', ['do' => 'skipWizard']);
        $velden = $skip->viewData('state')->toArray();
        unset($velden['costs'], $velden['parts']);

        $naFormWissel = $this->post('/', array_merge($velden, ['do' => "form:$vorm"]));
        $velden = $naFormWissel->viewData('state')->toArray();
        unset($velden['costs'], $velden['parts']);

        return $velden;
    }

    public function test_kiezen_van_verstrekker_past_eigen_vormopslag_toe(): void
    {
        $lender = Lender::factory()->create(['active' => true, 'delta' => 0.0, 'surcharge_av' => 0.9]);
        $velden = $this->naarFormEnStateVelden('av');

        $response = $this->post('/', array_merge($velden, ['do' => 'lender:' . $lender->id]));

        $response->assertOk();
        // basisrente (fallback nhg, 10 jaar vast) = 3,86 + 0 delta + 0,90 opslag = 4,76
        $response->assertSee('4,76', false);
    }

    public function test_kiezen_van_verstrekker_gebruikt_algemene_opslag_zonder_eigen_waarde(): void
    {
        $lender = Lender::factory()->create(['active' => true, 'delta' => 0.0, 'surcharge_av' => null]);
        $velden = $this->naarFormEnStateVelden('av');

        $response = $this->post('/', array_merge($velden, ['do' => 'lender:' . $lender->id]));

        $response->assertOk();
        // 3,86 + 0 delta + 0,20 (algemene IO-opslag) = 4,06
        $response->assertSee('4,06', false);
    }
}
