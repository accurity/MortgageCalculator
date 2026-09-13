<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\FixedPeriod;
use App\Models\Lender;
use App\Models\RiskClass;
use App\Models\User;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RateSetControllerTest extends TestCase
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
    private function volledigeVelden(float $percentage = 4.0): array
    {
        $rates = [];
        foreach (FixedPeriod::query()->pluck('id') as $periodeId) {
            foreach (RiskClass::query()->pluck('id') as $klasseId) {
                $rates[$periodeId][$klasseId] = (string)$percentage;
            }
        }

        return ['rates' => $rates, 'note' => 'testset'];
    }

    public function test_index_vereist_login(): void
    {
        $lender = Lender::factory()->create();

        $this->get("/admin/lenders/{$lender->id}/rate-sets")->assertRedirect('/login');
    }

    public function test_volledige_tariefset_aanmaken_wordt_actueel(): void
    {
        $lender = Lender::factory()->create();

        $response = $this->actingAs($this->admin())
            ->post("/admin/lenders/{$lender->id}/rate-sets", $this->volledigeVelden(3.75));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.lenders.rate-sets.index', $lender));

        $set = $lender->rateSets()->firstOrFail();
        $this->assertTrue($set->is_current);
        $this->assertCount(FixedPeriod::query()->count() * RiskClass::query()->count(), $set->rates);
        $this->assertSame(3.75, $set->rates->first()->percentage);
    }

    public function test_nieuwe_set_maakt_vorige_set_geschiedenis(): void
    {
        $lender = Lender::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/lenders/{$lender->id}/rate-sets", $this->volledigeVelden(3.75));
        $eersteSet = $lender->rateSets()->firstOrFail();

        $this->actingAs($admin)->post("/admin/lenders/{$lender->id}/rate-sets", $this->volledigeVelden(4.25));

        $this->assertFalse($eersteSet->refresh()->is_current);
        $tweedeSet = $lender->rateSets()->where('id', '!=', $eersteSet->id)->firstOrFail();
        $this->assertTrue($tweedeSet->is_current);
        $this->assertCount(2, $lender->rateSets()->get());
    }

    public function test_onvolledige_tariefset_wordt_geweigerd(): void
    {
        $lender = Lender::factory()->create();
        $velden = $this->volledigeVelden();
        $eerstePeriode = array_key_first($velden['rates']);
        unset($velden['rates'][$eerstePeriode]);

        $response = $this->actingAs($this->admin())
            ->post("/admin/lenders/{$lender->id}/rate-sets", $velden);

        $response->assertSessionHasErrors();
        $this->assertSame(0, $lender->rateSets()->count());
    }
}
