<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\FixedPeriod;
use App\Models\RiskClass;
use App\Models\User;
use App\Services\Mortgage\Calculator;
use App\Services\Mortgage\Constants;
use App\Services\Mortgage\Domain\RateRepository;
use App\Services\Mortgage\State;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SettingsControllerTest extends TestCase
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

    /** @return array<string, mixed> */
    private function geldigeVelden(array $overschrijf = []): array
    {
        $fallback = [];
        foreach (RiskClass::query()->pluck('code') as $code) {
            foreach (FixedPeriod::query()->pluck('years') as $jaren) {
                $fallback[$code][$jaren] = '4.00';
            }
        }

        return array_merge([
            'nhg_grens' => '435000',
            'io_surcharge_pct' => '0.20',
            'io_max_share_pct' => '50',
            'alarm_email' => 'admin@example.com',
            'fallback' => $fallback,
        ], $overschrijf);
    }

    public function test_edit_vereist_login(): void
    {
        $this->get('/admin/settings')->assertRedirect('/login');
    }

    public function test_instellingen_opslaan(): void
    {
        $response = $this->actingAs($this->admin())
            ->put('/admin/settings', $this->geldigeVelden(['nhg_grens' => '500000']));

        $response->assertSessionHasNoErrors();
        $this->assertSame(500000.0, RateRepository::nhgGrens());
    }

    public function test_gewijzigde_nhg_grens_verandert_de_indeling_direct(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', $this->geldigeVelden(['nhg_grens' => '100000']));

        $klasse = RateRepository::klasseVoor(86, 365000.0);

        $this->assertNotSame('nhg', $klasse->code);
    }

    public function test_gewijzigde_io_opslag_verandert_de_berekening(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', $this->geldigeVelden(['io_surcharge_pct' => '1.50']));

        $state = State::uitRequest(['do' => 'skipWizard', 'io' => '50000', 'rate' => '4.00']);
        $calc = new Calculator($state);

        $this->assertSame(5.5, $calc->ioRate());
    }

    public function test_gewijzigd_terugvalgemiddelde_verandert_de_marktrente(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', $this->geldigeVelden());

        $klasse = RiskClass::query()->where('code', 'nhg')->firstOrFail();
        $this->assertSame(4.0, RateRepository::rate(10, $klasse));
    }

    public function test_onvolledige_terugvaltabel_wordt_geweigerd(): void
    {
        $velden = $this->geldigeVelden();
        $eersteKlasse = array_key_first($velden['fallback']);
        unset($velden['fallback'][$eersteKlasse]);

        $response = $this->actingAs($this->admin())->put('/admin/settings', $velden);

        $response->assertSessionHasErrors();
    }

    public function test_ongeldig_emailadres_wordt_geweigerd(): void
    {
        $response = $this->actingAs($this->admin())
            ->put('/admin/settings', $this->geldigeVelden(['alarm_email' => 'niet-een-email']));

        $response->assertSessionHasErrors('alarm_email');
    }
}
