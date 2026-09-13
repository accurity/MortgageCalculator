<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Lender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScrapeStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function metBron(Lender $lender, bool $toegestaan = true): void
    {
        $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => $toegestaan,
        ]);
    }

    public function test_vereist_login(): void
    {
        $this->get(route('admin.rates.status'))->assertRedirect(route('login'));
    }

    public function test_toont_verouderd_boven_36_uur(): void
    {
        $lender = Lender::factory()->create(['name' => 'Oude Bank']);
        $this->metBron($lender);
        $lender->rateSets()->create(['is_current' => true])
            ->forceFill(['created_at' => now()->subHours(40)])->save();

        $response = $this->actingAs($this->admin())->get(route('admin.rates.status'));

        $response->assertOk();
        $response->assertSeeText('Oude Bank');
        $response->assertSee('color:#b91c1c;font-weight:600', false);
    }

    public function test_toont_niet_verouderd_onder_36_uur(): void
    {
        $lender = Lender::factory()->create(['name' => 'Verse Bank']);
        $this->metBron($lender);
        $lender->rateSets()->create(['is_current' => true])
            ->forceFill(['created_at' => now()->subHours(5)])->save();

        $response = $this->actingAs($this->admin())->get(route('admin.rates.status'));

        $response->assertOk();
        $response->assertDontSee('color:#b91c1c;font-weight:600', false);
    }

    public function test_toont_geen_actuele_set_zonder_tariefset(): void
    {
        $lender = Lender::factory()->create(['name' => 'Lege Bank']);
        $this->metBron($lender);

        $response = $this->actingAs($this->admin())->get(route('admin.rates.status'));

        $response->assertOk();
        $response->assertSeeText('Geen actuele set');
    }

    public function test_toont_laatste_run_status_en_melding(): void
    {
        $lender = Lender::factory()->create(['name' => 'Faal Bank']);
        $this->metBron($lender);
        $lender->scrapeRuns()->create([
            'is_dry_run' => false,
            'duration_ms' => 20,
            'rate_count' => 0,
            'status' => 'failed',
            'message' => 'Verbinding mislukt',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.rates.status'));

        $response->assertOk();
        $response->assertSeeText('Mislukt');
        $response->assertSeeText('Verbinding mislukt');
    }

    public function test_laat_verstrekkers_zonder_bron_weg(): void
    {
        Lender::factory()->create(['name' => 'Geen Bron Bank']);

        $response = $this->actingAs($this->admin())->get(route('admin.rates.status'));

        $response->assertOk();
        $response->assertDontSeeText('Geen Bron Bank');
    }
}
