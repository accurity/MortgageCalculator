<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\TaxYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaxYearControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function geldigeVelden(array $overschrijf = []): array
    {
        return array_merge([
            'jaar' => 2027,
            'schijven_tekst' => "38883:35.70\n79137:37.56\n:49.50",
            'max_aftrektarief_pct' => '37.56',
            'ewf_percentage_pct' => '0.35',
            'ewf_grens' => '1350000',
            'hillen_aandeel_pct' => '70',
        ], $overschrijf);
    }

    public function test_index_vereist_login(): void
    {
        $this->get('/admin/tax-years')->assertRedirect('/login');
    }

    public function test_index_toont_belastingjaren(): void
    {
        TaxYear::factory()->create(['jaar' => 2026]);

        $response = $this->actingAs($this->admin())->get('/admin/tax-years');

        $response->assertOk();
        $response->assertSee('2026');
    }

    public function test_nieuw_belastingjaar_aanmaken(): void
    {
        $response = $this->actingAs($this->admin())
            ->post('/admin/tax-years', $this->geldigeVelden());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.tax-years.index'));

        $jaar = TaxYear::query()->where('jaar', 2027)->firstOrFail();
        $this->assertSame(0.3756, $jaar->max_aftrektarief);
        $this->assertSame(0.0035, $jaar->ewf_percentage);
        $this->assertSame(1350000.0, $jaar->ewf_grens);
        $this->assertSame(0.70, $jaar->hillen_aandeel);
        $schijven = $jaar->schijven;
        $this->assertCount(3, $schijven);
        $this->assertEqualsWithDelta(38883.0, $schijven[0]['tot'], 0.001);
        $this->assertEqualsWithDelta(0.357, $schijven[0]['tarief'], 0.0001);
        $this->assertEqualsWithDelta(79137.0, $schijven[1]['tot'], 0.001);
        $this->assertEqualsWithDelta(0.3756, $schijven[1]['tarief'], 0.0001);
        $this->assertNull($schijven[2]['tot']);
        $this->assertEqualsWithDelta(0.495, $schijven[2]['tarief'], 0.0001);
    }

    public function test_schijven_moeten_oplopend_zijn(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/tax-years', $this->geldigeVelden([
            'schijven_tekst' => "79137:37.56\n38883:35.70\n:49.50",
        ]));

        $response->assertSessionHasErrors('schijven_tekst');
        $this->assertDatabaseMissing('tax_years', ['jaar' => 2027]);
    }

    public function test_laatste_schijf_moet_open_zijn(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/tax-years', $this->geldigeVelden([
            'schijven_tekst' => "38883:35.70\n79137:37.56",
        ]));

        $response->assertSessionHasErrors('schijven_tekst');
    }

    public function test_jaar_moet_uniek_zijn(): void
    {
        TaxYear::factory()->create(['jaar' => 2027]);

        $response = $this->actingAs($this->admin())->post('/admin/tax-years', $this->geldigeVelden());

        $response->assertSessionHasErrors('jaar');
    }

    public function test_belastingjaar_bewerken(): void
    {
        $jaar = TaxYear::factory()->create(['jaar' => 2027]);

        $response = $this->actingAs($this->admin())->put("/admin/tax-years/{$jaar->id}", $this->geldigeVelden([
            'hillen_aandeel_pct' => '50',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(0.50, $jaar->refresh()->hillen_aandeel);
    }

    public function test_belastingjaar_verwijderen(): void
    {
        $jaar = TaxYear::factory()->create(['jaar' => 2027]);

        $response = $this->actingAs($this->admin())->delete("/admin/tax-years/{$jaar->id}");

        $response->assertRedirect(route('admin.tax-years.index'));
        $this->assertDatabaseMissing('tax_years', ['id' => $jaar->id]);
    }
}
