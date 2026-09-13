<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TaxYearSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CalculatorTest extends TestCase
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

    public function test_homepage_toont_wizardstap_1(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Accurity', false);
        $response->assertSee('design-data', false);
    }

    public function test_skip_wizard_toont_geavanceerd_resultaat(): void
    {
        $response = $this->post('/', ['do' => 'skipWizard']);

        $response->assertOk();
        $response->assertSee('data-theme="light"', false);
    }
}
