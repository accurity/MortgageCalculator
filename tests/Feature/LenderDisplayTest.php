<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lender;
use Database\Seeders\TaxYearSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LenderDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TaxYearSeeder::class);
    }

    public function test_actieve_verstrekker_staat_in_de_design_data(): void
    {
        Lender::factory()->create(['name' => 'Zichtbare Bank', 'active' => true]);

        $response = $this->post('/', ['do' => 'skipWizard']);

        $response->assertOk();
        $response->assertSee('Zichtbare Bank', false);
    }

    public function test_inactieve_verstrekker_staat_niet_in_de_design_data(): void
    {
        Lender::factory()->create(['name' => 'Verborgen Bank', 'active' => false]);

        $response = $this->post('/', ['do' => 'skipWizard']);

        $response->assertOk();
        $response->assertDontSee('Verborgen Bank', false);
    }

    public function test_verstrekkers_staan_op_volgorde(): void
    {
        Lender::factory()->create(['name' => 'Tweede', 'sort_order' => 2]);
        Lender::factory()->create(['name' => 'Eerste', 'sort_order' => 1]);

        $response = $this->post('/', ['do' => 'skipWizard']);

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Tweede'),
            strpos($content, 'Eerste')
        );
    }

    public function test_kiezen_van_verstrekker_past_rente_toe_met_delta(): void
    {
        $lender = Lender::factory()->create(['name' => 'Kortingsbank', 'active' => true, 'delta' => -0.5]);

        $skip = $this->post('/', ['do' => 'skipWizard']);
        $velden = $skip->viewData('state')->toArray();
        unset($velden['costs'], $velden['parts']);

        $response = $this->post('/', array_merge($velden, [
            'do' => 'lender:' . $lender->id,
        ]));

        $response->assertOk();
        $response->assertSee((string)round($lender->delta, 2), false);
    }
}
