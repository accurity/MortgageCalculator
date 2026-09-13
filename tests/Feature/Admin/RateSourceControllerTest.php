<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Lender;
use App\Models\User;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class RateSourceControllerTest extends TestCase
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

    private function fixture(string $naam): string
    {
        return file_get_contents(__DIR__ . '/../../fixtures/scraping/' . $naam);
    }

    public function test_edit_vereist_login(): void
    {
        $lender = Lender::factory()->create();

        $this->get("/admin/lenders/{$lender->id}/source")->assertRedirect('/login');
    }

    public function test_bron_opslaan(): void
    {
        $lender = Lender::factory()->create();

        $response = $this->actingAs($this->admin())->put("/admin/lenders/{$lender->id}/source", [
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => 'periode,klasse,nhg,rente',
            'scraping_allowed' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $lender->refresh();
        $this->assertNotNull($lender->rateSource);
        $this->assertSame(['periode', 'klasse', 'nhg', 'rente'], $lender->rateSource->column_map);
        $this->assertTrue($lender->rateSource->scraping_allowed);
    }

    public function test_kolomtoewijzing_zonder_periode_wordt_geweigerd(): void
    {
        $lender = Lender::factory()->create();

        $response = $this->actingAs($this->admin())->put("/admin/lenders/{$lender->id}/source", [
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => 'klasse,nhg,rente',
        ]);

        $response->assertSessionHasErrors('column_map');
    }

    public function test_kolomtoewijzing_zonder_klasse_of_nhg_wordt_geweigerd(): void
    {
        $lender = Lender::factory()->create();

        $response = $this->actingAs($this->admin())->put("/admin/lenders/{$lender->id}/source", [
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => 'periode,rente',
        ]);

        $response->assertSessionHasErrors('column_map');
    }

    public function test_testrun_toont_resultaat_zonder_op_te_slaan(): void
    {
        Http::fake([
            '*/robots.txt' => Http::response('', 404),
            'voorbeeld.nl/tarieven' => Http::response($this->fixture('tarieven-geldig.html')),
        ]);
        $lender = Lender::factory()->create();
        $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);

        $response = $this->actingAs($this->admin())->post("/admin/lenders/{$lender->id}/source/test");

        $response->assertOk();
        $response->assertSee('3,75');
        $this->assertSame(0, $lender->rateSets()->count());
    }
}
