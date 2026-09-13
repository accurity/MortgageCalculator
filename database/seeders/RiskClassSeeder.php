<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RiskClass;
use Illuminate\Database\Seeder;

/**
 * Tariefklassen: NHG en de LTV-staffels uit het plan. NHG wordt bepaald door
 * de NHG-grens (instelling `nhg_grens`), niet door max_ltv - vandaar null.
 */
final class RiskClassSeeder extends Seeder
{
    public function run(): void
    {
        $klassen = [
            ['code' => 'nhg', 'name' => 'NHG', 'max_ltv' => null, 'nhg' => true, 'sort_order' => 0],
            ['code' => 'ltv67_5', 'name' => 'Tot 67,5% marktwaarde', 'max_ltv' => 67.5, 'nhg' => false, 'sort_order' => 1],
            ['code' => 'ltv90', 'name' => 'Tot 90% marktwaarde', 'max_ltv' => 90.0, 'nhg' => false, 'sort_order' => 2],
            ['code' => 'ltv100', 'name' => 'Tot 100% marktwaarde', 'max_ltv' => 100.0, 'nhg' => false, 'sort_order' => 3],
        ];

        foreach ($klassen as $klasse) {
            RiskClass::query()->updateOrCreate(['code' => $klasse['code']], $klasse);
        }
    }
}
