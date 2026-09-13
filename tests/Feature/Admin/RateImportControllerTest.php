<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Lender;
use App\Models\RateSet;
use App\Models\User;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class RateImportControllerTest extends TestCase
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

    private function csv(string $inhoud): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('tarieven.csv', $inhoud);
    }

    public function test_create_vereist_login(): void
    {
        $this->get('/admin/rate-imports/create')->assertRedirect('/login');
    }

    public function test_geldig_bestand_toont_voorbeeld_zonder_op_te_slaan(): void
    {
        $lender = Lender::factory()->create(['slug' => 'munt-hypotheken']);
        $inhoud = "slug,periode,klasse,nhg,rente\nmunt-hypotheken,10,ltv90,nee,3.75\n";

        $response = $this->actingAs($this->admin())
            ->post('/admin/rate-imports', ['file' => $this->csv($inhoud)]);

        $response->assertOk();
        $response->assertSee('3,75');
        $this->assertSame(0, $lender->rateSets()->count());
    }

    public function test_nhg_kolom_overschrijft_klasse(): void
    {
        $lender = Lender::factory()->create(['slug' => 'munt-hypotheken']);
        $inhoud = "slug,periode,klasse,nhg,rente\nmunt-hypotheken,10,irrelevant,ja,3.10\n";

        $response = $this->actingAs($this->admin())
            ->post('/admin/rate-imports', ['file' => $this->csv($inhoud)]);

        $response->assertOk();
        $response->assertSee('NHG');
    }

    public function test_bevestigen_maakt_de_import_actueel(): void
    {
        $lender = Lender::factory()->create(['slug' => 'munt-hypotheken']);
        $oudeSet = $lender->rateSets()->create(['is_current' => true]);
        $inhoud = "slug,periode,klasse,nhg,rente\nmunt-hypotheken,10,ltv90,nee,3.75\n";

        $admin = $this->admin();
        $preview = $this->actingAs($admin)->post('/admin/rate-imports', ['file' => $this->csv($inhoud)]);
        $payload = $preview->viewData('payload');

        $response = $this->actingAs($admin)->post('/admin/rate-imports/confirm', ['payload' => $payload]);

        $response->assertRedirect(route('admin.rate-imports.create'));
        $this->assertFalse($oudeSet->refresh()->is_current);
        $nieuweSet = RateSet::query()->where('lender_id', $lender->id)->where('is_current', true)->firstOrFail();
        $this->assertSame(3.75, $nieuweSet->rates->first()->percentage);
    }

    public function test_onbekende_verstrekker_blokkeert_de_hele_import(): void
    {
        Lender::factory()->create(['slug' => 'munt-hypotheken']);
        $inhoud = "slug,periode,klasse,nhg,rente\nonbekend-merk,10,ltv90,nee,3.75\nmunt-hypotheken,10,ltv90,nee,3.75\n";

        $response = $this->actingAs($this->admin())
            ->post('/admin/rate-imports', ['file' => $this->csv($inhoud)]);

        $response->assertOk();
        $response->assertSee('onbekende verstrekker');
        $response->assertViewIs('admin.rate-imports.create');
    }

    public function test_rente_buiten_grens_blokkeert_de_import(): void
    {
        Lender::factory()->create(['slug' => 'munt-hypotheken']);
        $inhoud = "slug,periode,klasse,nhg,rente\nmunt-hypotheken,10,ltv90,nee,25\n";

        $response = $this->actingAs($this->admin())
            ->post('/admin/rate-imports', ['file' => $this->csv($inhoud)]);

        $response->assertOk();
        $response->assertSee('redelijke grens');
    }

    public function test_onbekende_periode_blokkeert_de_import(): void
    {
        Lender::factory()->create(['slug' => 'munt-hypotheken']);
        $inhoud = "slug,periode,klasse,nhg,rente\nmunt-hypotheken,7,ltv90,nee,3.75\n";

        $response = $this->actingAs($this->admin())
            ->post('/admin/rate-imports', ['file' => $this->csv($inhoud)]);

        $response->assertOk();
        $response->assertSee('onbekende of inactieve periode');
    }

    public function test_dubbele_combinatie_blokkeert_de_import(): void
    {
        Lender::factory()->create(['slug' => 'munt-hypotheken']);
        $inhoud = "slug,periode,klasse,nhg,rente\nmunt-hypotheken,10,ltv90,nee,3.75\nmunt-hypotheken,10,ltv90,nee,3.80\n";

        $response = $this->actingAs($this->admin())
            ->post('/admin/rate-imports', ['file' => $this->csv($inhoud)]);

        $response->assertOk();
        $response->assertSee('dubbele combinatie');
    }

    public function test_ontbrekende_kolom_blokkeert_de_import(): void
    {
        Lender::factory()->create(['slug' => 'munt-hypotheken']);
        $inhoud = "slug,periode,klasse,rente\nmunt-hypotheken,10,ltv90,3.75\n";

        $response = $this->actingAs($this->admin())
            ->post('/admin/rate-imports', ['file' => $this->csv($inhoud)]);

        $response->assertOk();
        $response->assertSee('ontbreekt');
    }
}
