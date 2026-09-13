<?php

declare(strict_types=1);

namespace Tests\Feature\Scraping;

use App\Models\Lender;
use App\Models\Setting;
use App\Services\Mortgage\Domain\Scraping\StalenessNotifier;
use Database\Seeders\FixedPeriodSeeder;
use Database\Seeders\RiskClassSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class StalenessNotifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FixedPeriodSeeder::class);
        $this->seed(RiskClassSeeder::class);
        Setting::set('alarm_email', 'beheer@voorbeeld.nl');
    }

    private function metBron(array $overrides = []): Lender
    {
        $lender = Lender::factory()->create(array_merge(['active' => true], $overrides));
        $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => true,
        ]);

        return $lender;
    }

    public function test_stuurt_mail_boven_de_drempel(): void
    {
        Mail::fake();
        $lender = $this->metBron();
        $lender->rateSets()->create(['is_current' => true])->forceFill(['created_at' => now()->subHours(40)])->save();

        $aantal = StalenessNotifier::verstuurIndienNodig();

        $this->assertSame(1, $aantal);
        Mail::assertSent(\App\Mail\StaleRatesAlert::class, function ($mail) use ($lender) {
            return $mail->verouderd[0]['naam'] === $lender->name;
        });
    }

    public function test_stuurt_geen_mail_onder_de_drempel(): void
    {
        Mail::fake();
        $lender = $this->metBron();
        $lender->rateSets()->create(['is_current' => true])->forceFill(['created_at' => now()->subHours(10)])->save();

        $aantal = StalenessNotifier::verstuurIndienNodig();

        $this->assertSame(0, $aantal);
        Mail::assertNothingSent();
    }

    public function test_debounce_voorkomt_tweede_mail_binnen_24_uur(): void
    {
        Mail::fake();
        $lender = $this->metBron(['last_stale_alert_at' => now()->subHours(5)]);
        $lender->rateSets()->create(['is_current' => true])->forceFill(['created_at' => now()->subHours(40)])->save();

        $aantal = StalenessNotifier::verstuurIndienNodig();

        $this->assertSame(0, $aantal);
        Mail::assertNothingSent();
    }

    public function test_stuurt_opnieuw_na_debounce_periode(): void
    {
        Mail::fake();
        $lender = $this->metBron(['last_stale_alert_at' => now()->subHours(30)]);
        $lender->rateSets()->create(['is_current' => true])->forceFill(['created_at' => now()->subHours(40)])->save();

        $aantal = StalenessNotifier::verstuurIndienNodig();

        $this->assertSame(1, $aantal);
        Mail::assertSent(\App\Mail\StaleRatesAlert::class);
    }

    public function test_geen_mail_zonder_alarm_email_instelling(): void
    {
        Mail::fake();
        Setting::set('alarm_email', '');
        $lender = $this->metBron();
        $lender->rateSets()->create(['is_current' => true])->forceFill(['created_at' => now()->subHours(40)])->save();

        $aantal = StalenessNotifier::verstuurIndienNodig();

        $this->assertSame(0, $aantal);
        Mail::assertNothingSent();
    }

    public function test_negeert_verstrekkers_zonder_scrapetoestemming(): void
    {
        Mail::fake();
        $lender = Lender::factory()->create(['active' => true]);
        $lender->rateSource()->create([
            'url' => 'https://voorbeeld.nl/tarieven',
            'table_selector' => '#tarieven',
            'column_map' => ['periode', 'klasse', 'nhg', 'rente'],
            'scraping_allowed' => false,
        ]);
        $lender->rateSets()->create(['is_current' => true])->forceFill(['created_at' => now()->subHours(40)])->save();

        $aantal = StalenessNotifier::verstuurIndienNodig();

        $this->assertSame(0, $aantal);
        Mail::assertNothingSent();
    }

    public function test_negeert_inactieve_verstrekkers(): void
    {
        Mail::fake();
        $lender = $this->metBron(['active' => false]);
        $lender->rateSets()->create(['is_current' => true])->forceFill(['created_at' => now()->subHours(40)])->save();

        $aantal = StalenessNotifier::verstuurIndienNodig();

        $this->assertSame(0, $aantal);
        Mail::assertNothingSent();
    }
}
