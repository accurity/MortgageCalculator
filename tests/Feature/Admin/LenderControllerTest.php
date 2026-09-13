<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Lender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class LenderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function geldigeVelden(array $overschrijf = []): array
    {
        return array_merge([
            'slug' => 'nieuwe-bank',
            'name' => 'Nieuwe Bank',
            'description' => 'Een testverstrekker.',
            'apply_url' => 'https://voorbeeld.nl/aanvragen',
            'afm_number' => '12345678',
            'active' => '1',
            'sort_order' => 10,
            'delta' => '0.05',
        ], $overschrijf);
    }

    public function test_index_vereist_login(): void
    {
        $this->get('/admin/lenders')->assertRedirect('/login');
    }

    public function test_index_toont_verstrekkers(): void
    {
        Lender::factory()->create(['name' => 'Munt Hypotheken']);

        $response = $this->actingAs($this->admin())->get('/admin/lenders');

        $response->assertOk();
        $response->assertSee('Munt Hypotheken');
    }

    public function test_nieuwe_verstrekker_aanmaken(): void
    {
        $response = $this->actingAs($this->admin())
            ->post('/admin/lenders', $this->geldigeVelden());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.lenders.index'));

        $lender = Lender::query()->where('slug', 'nieuwe-bank')->firstOrFail();
        $this->assertSame('Nieuwe Bank', $lender->name);
        $this->assertTrue($lender->active);
        $this->assertSame(0.05, $lender->delta);
    }

    public function test_slug_moet_uniek_zijn(): void
    {
        Lender::factory()->create(['slug' => 'nieuwe-bank']);

        $response = $this->actingAs($this->admin())->post('/admin/lenders', $this->geldigeVelden());

        $response->assertSessionHasErrors('slug');
    }

    public function test_slug_moet_geldig_formaat_hebben(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/lenders', $this->geldigeVelden([
            'slug' => 'Niet Geldig!',
        ]));

        $response->assertSessionHasErrors('slug');
    }

    public function test_verstrekker_bewerken_wijzigt_slug_niet(): void
    {
        $lender = Lender::factory()->create(['slug' => 'oude-slug', 'name' => 'Oud']);

        $response = $this->actingAs($this->admin())->put("/admin/lenders/{$lender->id}", $this->geldigeVelden([
            'name' => 'Nieuwe Naam',
            'slug' => 'zou-genegeerd-moeten-worden',
        ]));

        $response->assertSessionHasNoErrors();
        $lender->refresh();
        $this->assertSame('oude-slug', $lender->slug);
        $this->assertSame('Nieuwe Naam', $lender->name);
    }

    public function test_verstrekker_verwijderen(): void
    {
        $lender = Lender::factory()->create();

        $response = $this->actingAs($this->admin())->delete("/admin/lenders/{$lender->id}");

        $response->assertRedirect(route('admin.lenders.index'));
        $this->assertDatabaseMissing('lenders', ['id' => $lender->id]);
    }

    public function test_logo_upload_accepteert_png(): void
    {
        $bestand = UploadedFile::fake()->create('logo.png', 100, 'image/png');

        $response = $this->actingAs($this->admin())->post('/admin/lenders', $this->geldigeVelden([
            'slug' => 'met-logo',
            'logo' => $bestand,
        ]));

        $response->assertSessionHasNoErrors();
        $lender = Lender::query()->where('slug', 'met-logo')->firstOrFail();
        $this->assertNotNull($lender->logo_path);
        $this->assertFileExists(public_path($lender->logo_path));

        @unlink(public_path($lender->logo_path));
    }

    public function test_logo_upload_wijst_verkeerd_mimetype_af(): void
    {
        $bestand = UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin())->post('/admin/lenders', $this->geldigeVelden([
            'slug' => 'fout-logo',
            'logo' => $bestand,
        ]));

        $response->assertSessionHasErrors('logo');
    }

    public function test_logo_upload_wijst_te_groot_bestand_af(): void
    {
        $bestand = UploadedFile::fake()->create('logo.png', 1024, 'image/png');

        $response = $this->actingAs($this->admin())->post('/admin/lenders', $this->geldigeVelden([
            'slug' => 'te-groot',
            'logo' => $bestand,
        ]));

        $response->assertSessionHasErrors('logo');
    }

    public function test_logo_url_als_alternatief_voor_upload(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/lenders', $this->geldigeVelden([
            'slug' => 'externe-url',
            'logo_url' => 'https://voorbeeld.nl/logo.png',
        ]));

        $response->assertSessionHasNoErrors();
        $lender = Lender::query()->where('slug', 'externe-url')->firstOrFail();
        $this->assertNull($lender->logo_path);
        $this->assertSame('https://voorbeeld.nl/logo.png', $lender->logo_url);
    }

    public function test_zonder_logo_geeft_geen_url(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/lenders', $this->geldigeVelden([
            'slug' => 'zonder-logo',
        ]));

        $response->assertSessionHasNoErrors();
        $lender = Lender::query()->where('slug', 'zonder-logo')->firstOrFail();
        $this->assertNull($lender->logoUrl());
    }
}
