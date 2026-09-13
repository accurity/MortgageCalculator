<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * NHG-grens, de terugvalgemiddelden per tariefklasse en periode (gebruikt
 * zolang er nog geen, of geen volledige, actuele tariefset ligt), de
 * aflossingsvrij-opslag en het maximale aflossingsvrije aandeel. De waarden
 * hier zijn gelijk aan wat vóór deze migraties hardgecodeerd in Constants en
 * Calculator::ltvAdj() stonden, zodat de bestaande schermen niet veranderen
 * totdat een beheerder ze zelf wijzigt.
 */
final class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('nhg_grens', '435000');
        Setting::set('io_surcharge', '0.20');
        Setting::set('io_max_share', '0.5');
        Setting::set('alarm_email', '');

        Setting::set('fallback_rates', json_encode([
            'nhg' => [1 => 4.11, 5 => 3.76, 10 => 3.86, 20 => 4.16, 30 => 4.36],
            'ltv67_5' => [1 => 3.90, 5 => 3.55, 10 => 3.65, 20 => 3.95, 30 => 4.15],
            'ltv90' => [1 => 4.03, 5 => 3.68, 10 => 3.78, 20 => 4.08, 30 => 4.28],
            'ltv100' => [1 => 4.15, 5 => 3.80, 10 => 3.90, 20 => 4.20, 30 => 4.40],
        ], JSON_THROW_ON_ERROR));
    }
}
