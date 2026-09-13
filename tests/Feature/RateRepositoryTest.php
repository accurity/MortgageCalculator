<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FixedPeriod;
use App\Models\Lender;
use App\Models\RiskClass;
use App\Services\Mortgage\Domain\RateRepository;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RateRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FixedPeriodSeeder::class);
        $this->seed(RiskClassSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    public function test_klasseVoor_kiest_nhg_binnen_de_grens(): void
    {
        $klasse = RateRepository::klasseVoor(86, 365000.0);

        $this->assertSame('nhg', $klasse->code);
    }

    public function test_klasseVoor_kiest_ltv_staffel_boven_de_nhg_grens(): void
    {
        $klasse = RateRepository::klasseVoor(86, 500000.0);

        $this->assertSame('ltv90', $klasse->code);
    }

    public function test_klasseVoor_valt_terug_op_hoogste_klasse_boven_100_procent(): void
    {
        $klasse = RateRepository::klasseVoor(118, 500000.0);

        $this->assertSame('ltv100', $klasse->code);
    }

    public function test_zonder_tariefsets_gebruikt_de_terugvalgemiddelden(): void
    {
        $klasse = RiskClass::query()->where('code', 'nhg')->firstOrFail();

        $this->assertSame(3.86, RateRepository::rate(10, $klasse));
    }

    public function test_actuele_tariefset_overschrijft_de_terugval(): void
    {
        $lender = Lender::factory()->create(['active' => true]);
        $periode = FixedPeriod::query()->where('years', 10)->firstOrFail();
        $klasse = RiskClass::query()->where('code', 'nhg')->firstOrFail();

        $set = $lender->rateSets()->create(['is_current' => true]);
        $set->rates()->create(['fixed_period_id' => $periode->id, 'risk_class_id' => $klasse->id, 'percentage' => 2.5]);
        RateRepository::verversCache();

        $this->assertSame(2.5, RateRepository::rate(10, $klasse));
    }

    public function test_inactieve_verstrekker_telt_niet_mee_in_het_gemiddelde(): void
    {
        $periode = FixedPeriod::query()->where('years', 10)->firstOrFail();
        $klasse = RiskClass::query()->where('code', 'nhg')->firstOrFail();

        $actief = Lender::factory()->create(['active' => true]);
        $set = $actief->rateSets()->create(['is_current' => true]);
        $set->rates()->create(['fixed_period_id' => $periode->id, 'risk_class_id' => $klasse->id, 'percentage' => 2.0]);

        $inactief = Lender::factory()->create(['active' => false]);
        $setInactief = $inactief->rateSets()->create(['is_current' => true]);
        $setInactief->rates()->create(['fixed_period_id' => $periode->id, 'risk_class_id' => $klasse->id, 'percentage' => 10.0]);

        RateRepository::verversCache();

        $this->assertSame(2.0, RateRepository::rate(10, $klasse));
    }

    public function test_oude_niet_actuele_set_telt_niet_mee(): void
    {
        $lender = Lender::factory()->create(['active' => true]);
        $periode = FixedPeriod::query()->where('years', 10)->firstOrFail();
        $klasse = RiskClass::query()->where('code', 'nhg')->firstOrFail();

        $oud = $lender->rateSets()->create(['is_current' => false]);
        $oud->rates()->create(['fixed_period_id' => $periode->id, 'risk_class_id' => $klasse->id, 'percentage' => 9.0]);

        RateRepository::verversCache();

        $this->assertSame(3.86, RateRepository::rate(10, $klasse));
    }
}
