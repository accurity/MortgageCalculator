<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain;

use App\Models\FixedPeriod;
use App\Models\RiskClass;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * De marktrente per rentevaste periode en tariefklasse.
 *
 * Voor elke combinatie is de rente het gemiddelde van de actuele tariefsets
 * (rate_sets.is_current) van actieve verstrekkers. Ontbreekt dat (nog geen
 * tarieven ingevoerd, of een lege cel), dan valt de calculator terug op de
 * vaste gemiddelden uit de instelling `fallback_rates`, zodat de site nooit
 * zonder rente staat. Dezelfde tabel voedt zowel de PHP- als de JS-render:
 * beide lezen 'm via Constants::forJs(), niet elk hun eigen berekening.
 */
final class RateRepository
{
    /** @var array<string, RiskClass>|null code => klasse */
    private static ?array $klassen = null;

    /** @var array<string, array<int, float>>|null klassecode => jaar => percentage */
    private static ?array $tabel = null;

    private static ?float $nhgGrens = null;

    public static function verversCache(): void
    {
        self::$klassen = null;
        self::$tabel = null;
        self::$nhgGrens = null;
    }

    public static function nhgGrens(): float
    {
        if (self::$nhgGrens === null) {
            self::$nhgGrens = (float)Setting::get('nhg_grens', '435000');
        }

        return self::$nhgGrens;
    }

    /** @return array<string, RiskClass> code => klasse, op volgorde */
    private static function klassen(): array
    {
        if (self::$klassen === null) {
            self::$klassen = RiskClass::query()->orderBy('sort_order')->get()
                ->mapWithKeys(static fn (RiskClass $k) => [$k->code => $k])
                ->all();
            if (self::$klassen === []) {
                throw new \RuntimeException('Geen tariefklassen beschikbaar; vul de admin of draai de seeder.');
            }
        }

        return self::$klassen;
    }

    /** De klasse zonder LTV-plafond: het plafond voor een lening boven elke staffel. */
    public static function hoogsteKlasse(): RiskClass
    {
        $nietNhg = array_values(array_filter(self::klassen(), static fn (RiskClass $k) => !$k->nhg));

        return end($nietNhg);
    }

    /**
     * De tariefklasse voor een gegeven LTV en leningbedrag: NHG als de lening
     * binnen de NHG-grens blijft, anders de eerste LTV-staffel die de LTV
     * nog dekt, en anders het plafond (hoogsteKlasse()).
     */
    public static function klasseVoor(int $ltvProcent, float $lening): RiskClass
    {
        $klassen = self::klassen();

        if ($ltvProcent <= 100 && $lening <= self::nhgGrens()) {
            foreach ($klassen as $k) {
                if ($k->nhg) {
                    return $k;
                }
            }
        }

        foreach ($klassen as $k) {
            if (!$k->nhg && $k->max_ltv !== null && $ltvProcent <= $k->max_ltv) {
                return $k;
            }
        }

        return self::hoogsteKlasse();
    }

    /** @return array<string, array<int, float>> klassecode => jaar => percentage */
    private static function tabel(): array
    {
        if (self::$tabel !== null) {
            return self::$tabel;
        }

        $fallback = json_decode(Setting::get('fallback_rates', '{}') ?? '{}', true) ?: [];
        $periodes = FixedPeriod::query()->orderBy('years')->pluck('years')->all();

        $echt = DB::table('rates')
            ->join('rate_sets', 'rate_sets.id', '=', 'rates.rate_set_id')
            ->join('lenders', 'lenders.id', '=', 'rate_sets.lender_id')
            ->join('fixed_periods', 'fixed_periods.id', '=', 'rates.fixed_period_id')
            ->join('risk_classes', 'risk_classes.id', '=', 'rates.risk_class_id')
            ->where('rate_sets.is_current', true)
            ->where('lenders.active', true)
            ->selectRaw('fixed_periods.years as jaar, risk_classes.code as klasse, AVG(rates.percentage) as gemiddelde')
            ->groupBy('fixed_periods.years', 'risk_classes.code')
            ->get();

        $perCel = [];
        foreach ($echt as $rij) {
            $perCel[$rij->klasse][(int)$rij->jaar] = round((float)$rij->gemiddelde, 2);
        }

        $tabel = [];
        foreach (array_keys(self::klassen()) as $code) {
            foreach ($periodes as $jaar) {
                $tabel[$code][$jaar] = $perCel[$code][$jaar]
                    ?? $fallback[$code][(string)$jaar]
                    ?? $fallback[$code][$jaar]
                    ?? 4.0;
            }
        }

        return self::$tabel = $tabel;
    }

    public static function rate(int $jaar, RiskClass $klasse): float
    {
        return self::tabel()[$klasse->code][$jaar] ?? 4.0;
    }

    /** Volledige tabel voor de client-side render (Constants::forJs()). */
    public static function tabelVoorJs(): array
    {
        return self::tabel();
    }

    /** @return array<int, array{code: string, maxLtv: float|null, nhg: bool}> voor de client-side classificatie */
    public static function klassenVoorJs(): array
    {
        return array_values(array_map(
            static fn (RiskClass $k): array => ['code' => $k->code, 'maxLtv' => $k->max_ltv, 'nhg' => $k->nhg],
            self::klassen()
        ));
    }
}
