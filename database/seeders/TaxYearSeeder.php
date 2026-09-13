<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TaxYear;
use Illuminate\Database\Seeder;

/**
 * Vult de belastingjaren met de waarden die tot deze migratie hardgecodeerd
 * stonden in TaxRules. Controleer bij de Belastingdienst voordat een nieuw
 * jaar hierop gebaseerd wordt.
 */
final class TaxYearSeeder extends Seeder
{
    public function run(): void
    {
        $jaren = [
            2024 => [
                'schijven' => [
                    ['tot' => 75518.0, 'tarief' => 0.3697],
                    ['tot' => null, 'tarief' => 0.4950],
                ],
                'max_aftrektarief' => 0.3697,
                'ewf_percentage' => 0.0035,
                'ewf_grens' => 1310000.0,
                'hillen_aandeel' => 0.8000,
            ],
            2025 => [
                'schijven' => [
                    ['tot' => 38441.0, 'tarief' => 0.3582],
                    ['tot' => 76817.0, 'tarief' => 0.3748],
                    ['tot' => null, 'tarief' => 0.4950],
                ],
                'max_aftrektarief' => 0.3748,
                'ewf_percentage' => 0.0035,
                'ewf_grens' => 1330000.0,
                'hillen_aandeel' => 0.7667,
            ],
            2026 => [
                'schijven' => [
                    ['tot' => 38883.0, 'tarief' => 0.3570],
                    ['tot' => 79137.0, 'tarief' => 0.3756],
                    ['tot' => null, 'tarief' => 0.4950],
                ],
                'max_aftrektarief' => 0.3756,
                'ewf_percentage' => 0.0035,
                'ewf_grens' => 1350000.0,
                'hillen_aandeel' => 0.7333,
            ],
        ];

        foreach ($jaren as $jaar => $gegevens) {
            TaxYear::query()->updateOrCreate(['jaar' => $jaar], $gegevens);
        }
    }
}
