<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_maakt_beheerder_aan(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Beheerder',
            '--email' => 'beheerder@example.com',
            '--password' => 'wachtwoord123',
        ])->assertSuccessful();

        $user = User::where('email', 'beheerder@example.com')->firstOrFail();
        $this->assertSame('Beheerder', $user->name);
        $this->assertTrue(Hash::check('wachtwoord123', $user->password));
    }

    public function test_weigert_dubbel_e_mailadres(): void
    {
        User::factory()->create(['email' => 'bestaat@example.com']);

        $this->artisan('admin:create', [
            '--name' => 'Nieuw',
            '--email' => 'bestaat@example.com',
            '--password' => 'wachtwoord123',
        ])->assertFailed();
    }
}
