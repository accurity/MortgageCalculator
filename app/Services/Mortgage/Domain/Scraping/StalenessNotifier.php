<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain\Scraping;

use App\Mail\StaleRatesAlert;
use App\Models\Lender;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

/**
 * Waarschuwt de beheerder zodra de actuele tariefset van een gescrapete
 * verstrekker ouder is dan 36 uur - typisch een teken dat de scraper voor
 * die verstrekker al een tijd faalt. Ten hoogste één e-mail per dag per
 * verstrekker (Lender::last_stale_alert_at).
 */
final class StalenessNotifier
{
    private const DREMPEL_UREN = 36;
    private const DEBOUNCE_UREN = 24;

    /** @return int aantal verstrekkers waarvoor een waarschuwing is verstuurd */
    public static function verstuurIndienNodig(): int
    {
        $nu = now();
        $verouderd = [];

        $lenders = Lender::query()
            ->where('active', true)
            ->whereHas('rateSource', static fn ($q) => $q->where('scraping_allowed', true))
            ->get();

        foreach ($lenders as $lender) {
            $actueleSet = $lender->rateSets()->where('is_current', true)->first();
            if ($actueleSet === null) {
                continue;
            }

            $leeftijdUren = $actueleSet->created_at->diffInMinutes($nu) / 60.0;
            if ($leeftijdUren < self::DREMPEL_UREN) {
                continue;
            }

            if ($lender->last_stale_alert_at !== null && $lender->last_stale_alert_at->diffInHours($nu) < self::DEBOUNCE_UREN) {
                continue;
            }

            $verouderd[] = ['lender' => $lender, 'naam' => $lender->name, 'leeftijdUren' => $leeftijdUren];
        }

        if ($verouderd === []) {
            return 0;
        }

        $emailadres = Setting::get('alarm_email', '');
        if (!$emailadres) {
            return 0;
        }

        Mail::to($emailadres)->send(new StaleRatesAlert(array_map(
            static fn (array $r): array => ['naam' => $r['naam'], 'leeftijdUren' => $r['leeftijdUren']],
            $verouderd
        )));

        foreach ($verouderd as $rij) {
            $rij['lender']->update(['last_stale_alert_at' => $nu]);
        }

        return count($verouderd);
    }
}
