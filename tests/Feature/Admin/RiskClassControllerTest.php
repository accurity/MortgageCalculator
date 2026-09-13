<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Lender;
use App\Models\RiskClass;
use App\Models\User;
use App\Services\Mortgage\Domain\RateRepository;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RiskClassControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FixedPeriodSeeder::class);
        $this->seed(RiskClassSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_index_vereist_login(): void
    {
        $this->get('/admin/risk-classes')->assertRedirect('/login');
    }

    public function test_nieuwe_klasse_aanmaken(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/risk-classes', [
            'name' => 'Tot 80% marktwaarde',
            'max_ltv' => '80',
            'sort_order' => 5,
            'active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $klasse = RiskClass::query()->where('name', 'Tot 80% marktwaarde')->firstOrFail();
        $this->assertSame('tot_80_marktwaarde', $klasse->code);
        $this->assertSame(80.0, $klasse->max_ltv);
        $this->assertFalse($klasse->nhg);
    }

    public function test_slechts_een_klasse_mag_de_nhg_vlag_hebben(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/risk-classes', [
            'name' => 'Nog een NHG',
            'nhg' => '1',
            'sort_order' => 5,
            'active' => '1',
        ]);

        $response->assertSessionHasErrors('nhg');
    }

    public function test_klasse_bewerken_wijzigt_code_niet(): void
    {
        $klasse = RiskClass::query()->where('code', 'ltv90')->firstOrFail();

        $response = $this->actingAs($this->admin())->put("/admin/risk-classes/{$klasse->id}", [
            'name' => 'Tot 90% marktwaarde (gewijzigd)',
            'max_ltv' => '85',
            'sort_order' => $klasse->sort_order,
            'active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $klasse->refresh();
        $this->assertSame('ltv90', $klasse->code);
        $this->assertSame(85.0, $klasse->max_ltv);
    }

    public function test_gebruikte_klasse_kan_niet_verwijderd_worden(): void
    {
        $klasse = RiskClass::query()->where('code', 'nhg')->firstOrFail();
        $lender = Lender::factory()->create();
        $periode = \App\Models\FixedPeriod::query()->first();
        $set = $lender->rateSets()->create(['is_current' => true]);
        $set->rates()->create(['fixed_period_id' => $periode->id, 'risk_class_id' => $klasse->id, 'percentage' => 3.5]);

        $response = $this->actingAs($this->admin())->delete("/admin/risk-classes/{$klasse->id}");

        $response->assertRedirect(route('admin.risk-classes.index'));
        $this->assertDatabaseHas('risk_classes', ['id' => $klasse->id]);
    }

    public function test_ongebruikte_klasse_kan_verwijderd_worden(): void
    {
        $klasse = RiskClass::factory()->create(['code' => 'ongebruikt']);

        $response = $this->actingAs($this->admin())->delete("/admin/risk-classes/{$klasse->id}");

        $response->assertRedirect(route('admin.risk-classes.index'));
        $this->assertDatabaseMissing('risk_classes', ['id' => $klasse->id]);
    }

    public function test_inactieve_klasse_telt_niet_mee_in_de_indeling(): void
    {
        $klasse = RiskClass::query()->where('code', 'ltv67_5')->firstOrFail();
        $klasse->update(['active' => false]);
        RateRepository::verversCache();

        $gekozen = RateRepository::klasseVoor(65, 500000.0);

        $this->assertNotSame('ltv67_5', $gekozen->code);
    }

    public function test_gewijzigde_bovengrens_verandert_de_indeling(): void
    {
        $klasse = RiskClass::query()->where('code', 'ltv90')->firstOrFail();
        $klasse->update(['max_ltv' => 70.0]);
        RateRepository::verversCache();

        $gekozen = RateRepository::klasseVoor(75, 500000.0);

        $this->assertNotSame('ltv90', $gekozen->code);
    }
}
