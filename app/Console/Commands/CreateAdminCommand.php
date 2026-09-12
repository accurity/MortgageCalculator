<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Maakt een beheerder aan. Er is geen openbare registratie: dit commando is
 * de enige manier om een account te krijgen.
 */
final class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create {--name=} {--email=} {--password=}';

    protected $description = 'Maak een beheerder aan die kan inloggen op /admin';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Naam');
        $email = $this->option('email') ?: $this->ask('E-mailadres');
        $password = $this->option('password') ?: $this->secret('Wachtwoord');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $fout) {
                $this->error($fout);
            }

            return self::FAILURE;
        }

        $gegevens = $validator->validated();

        User::create([
            'name' => $gegevens['name'],
            'email' => $gegevens['email'],
            'password' => Hash::make($gegevens['password']),
        ]);

        $this->info("Beheerder aangemaakt: {$gegevens['email']}");

        return self::SUCCESS;
    }
}
