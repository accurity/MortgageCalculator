<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\FixedPeriod;
use App\Models\Lender;
use App\Models\RiskClass;
use App\Models\User;
use App\Services\Mortgage\Domain\RateRepository;
use App\Services\Mortgage\Constants;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FixedPeriodControllerTest extends TestCase
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
        $this->get('/admin/fixed-periods')->assertRedirect('/login');
    }

    public function test_nieuwe_periode_aanmaken(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/fixed-periods', [
            'years' => 15,
            'sort_order' => 6,
            'active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fixed_periods', ['years' => 15]);
        $this->assertContains(15, Constants::fixedOptions());
    }

    public function test_jaren_moeten_uniek_zijn(): void
    {
        FixedPeriod::factory()->create(['years' => 15]);

        $response = $this->actingAs($this->admin())->post('/admin/fixed-periods', [
            'years' => 15,
            'sort_order' => 6,
            'active' => '1',
        ]);

        $response->assertSessionHasErrors('years');
    }

    public function test_periode_bewerken_wijzigt_jaren_niet(): void
    {
        $periode = FixedPeriod::query()->where('years', 10)->firstOrFail();

        $response = $this->actingAs($this->admin())->put("/admin/fixed-periods/{$periode->id}", [
            'years' => 99,
            'sort_order' => 1,
            'active' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(10, $periode->refresh()->years);
    }

    public function test_gebruikte_periode_kan_niet_verwijderd_worden(): void
    {
        $periode = FixedPeriod::query()->where('years', 10)->firstOrFail();
        $klasse = RiskClass::query()->first();
        $lender = Lender::factory()->create();
        $set = $lender->rateSets()->create(['is_current' => true]);
        $set->rates()->create(['fixed_period_id' => $periode->id, 'risk_class_id' => $klasse->id, 'percentage' => 3.5]);

        $response = $this->actingAs($this->admin())->delete("/admin/fixed-periods/{$periode->id}");

        $response->assertRedirect(route('admin.fixed-periods.index'));
        $this->assertDatabaseHas('fixed_periods', ['id' => $periode->id]);
    }

    public function test_inactieve_periode_verschijnt_niet_meer_als_optie(): void
    {
        $periode = FixedPeriod::query()->where('years', 1)->firstOrFail();
        $periode->update(['active' => false]);
        RateRepository::verversCache();

        $this->assertNotContains(1, Constants::fixedOptions());
    }
}
