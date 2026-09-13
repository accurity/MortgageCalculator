<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FixedPeriod;
use Illuminate\Database\Seeder;

/** Rentevaste periodes uit het ontwerp: 1, 5, 10, 20 en 30 jaar. */
final class FixedPeriodSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([1, 5, 10, 20, 30] as $i => $jaren) {
            FixedPeriod::query()->updateOrCreate(['years' => $jaren], ['sort_order' => $i]);
        }
    }
}
