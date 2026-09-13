<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Lender;
use Illuminate\Database\Seeder;

/**
 * Vult de verstrekkers die tot deze migratie hardgecodeerd stonden in
 * data/lenders.php. Voorbeelddata, geen echte marktrentes.
 */
final class LenderSeeder extends Seeder
{
    public function run(): void
    {
        $verstrekkers = [
            ['slug' => 'munt-hypotheken', 'name' => 'Munt Hypotheken', 'delta' => -0.14, 'sort_order' => 1, 'description' => 'tot 67% marktwaarde'],
            ['slug' => 'tulp-hypotheken', 'name' => 'Tulp Hypotheken', 'delta' => -0.06, 'sort_order' => 2, 'description' => 'tot 90% marktwaarde'],
            ['slug' => 'asr', 'name' => 'ASR', 'delta' => 0.02, 'sort_order' => 3, 'description' => 'geen bereidstellingsprovisie'],
            ['slug' => 'rabobank', 'name' => 'Rabobank', 'delta' => 0.09, 'sort_order' => 4, 'description' => 'met duurzaamheidskorting'],
            ['slug' => 'ing', 'name' => 'ING', 'delta' => 0.15, 'sort_order' => 5, 'description' => 'boetevrij 20% aflossen'],
        ];

        foreach ($verstrekkers as $rij) {
            Lender::query()->updateOrCreate(['slug' => $rij['slug']], $rij + ['active' => true]);
        }
    }
}
