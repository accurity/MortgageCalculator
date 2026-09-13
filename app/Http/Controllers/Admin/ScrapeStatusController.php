<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lender;
use Illuminate\View\View;

final class ScrapeStatusController extends Controller
{
    private const DREMPEL_UREN = 36;

    public function index(): View
    {
        $rijen = Lender::query()
            ->whereHas('rateSource')
            ->with(['rateSource', 'scrapeRuns' => fn ($q) => $q->latest('id')->limit(1)])
            ->orderBy('name')
            ->get()
            ->map(function (Lender $lender): array {
                $laatsteRun = $lender->scrapeRuns->first();
                $actueleSet = $lender->rateSets()->where('is_current', true)->first();
                $leeftijdUren = $actueleSet === null ? null : $actueleSet->created_at->diffInMinutes(now()) / 60.0;

                return [
                    'lender' => $lender,
                    'laatsteRun' => $laatsteRun,
                    'leeftijdUren' => $leeftijdUren,
                    'verouderd' => $leeftijdUren !== null && $leeftijdUren > self::DREMPEL_UREN,
                ];
            });

        return view('admin.rates-status.index', ['rijen' => $rijen, 'drempelUren' => self::DREMPEL_UREN]);
    }
}
