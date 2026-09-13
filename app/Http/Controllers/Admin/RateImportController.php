<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RateImportRequest;
use App\Models\FixedPeriod;
use App\Models\Lender;
use App\Models\RateSet;
use App\Models\RiskClass;
use App\Services\Mortgage\Domain\RateImporter;
use App\Services\Mortgage\Domain\RateRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CSV-import van tarieven voor verstrekkers die niet gescraped mogen of
 * kunnen worden. Twee stappen: store() leest het bestand in en toont een
 * voorbeeld (of de foutlijst), confirm() slaat de al gevalideerde rijen pas
 * echt op - zonder het bestand opnieuw te hoeven uploaden.
 */
final class RateImportController extends Controller
{
    public function create(): View
    {
        return view('admin.rate-imports.create');
    }

    public function store(RateImportRequest $request): View
    {
        $inhoud = $request->file('file')->get();
        $resultaat = RateImporter::verwerk($inhoud);

        if (!$resultaat->geldig()) {
            return view('admin.rate-imports.create', ['fouten' => $resultaat->fouten]);
        }

        return view('admin.rate-imports.preview', [
            'rijen' => $resultaat->rijen,
            'payload' => json_encode($resultaat->rijen, JSON_THROW_ON_ERROR),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $rijen = json_decode((string)$request->input('payload', '[]'), true) ?: [];

        $geldigeLenders = Lender::query()->pluck('id')->all();
        $geldigePeriodes = FixedPeriod::query()->where('active', true)->pluck('id')->all();
        $geldigeKlassen = RiskClass::query()->where('active', true)->pluck('id')->all();

        foreach ($rijen as $rij) {
            if (
                !in_array($rij['lender_id'], $geldigeLenders, true)
                || !in_array($rij['fixed_period_id'], $geldigePeriodes, true)
                || !in_array($rij['risk_class_id'], $geldigeKlassen, true)
            ) {
                return redirect()->route('admin.rate-imports.create')
                    ->with('fout', 'De gegevens zijn gewijzigd sinds het voorbeeld; upload het bestand opnieuw.');
            }
        }

        DB::transaction(function () use ($rijen): void {
            foreach (collect($rijen)->groupBy('lender_id') as $lenderId => $rijenVoorLender) {
                RateSet::query()->where('lender_id', $lenderId)->update(['is_current' => false]);
                $set = RateSet::query()->create([
                    'lender_id' => $lenderId,
                    'is_current' => true,
                    'note' => 'CSV-import',
                ]);
                foreach ($rijenVoorLender as $rij) {
                    $set->rates()->create([
                        'fixed_period_id' => $rij['fixed_period_id'],
                        'risk_class_id' => $rij['risk_class_id'],
                        'percentage' => $rij['percentage'],
                    ]);
                }
            }
        });

        RateRepository::verversCache();

        return redirect()->route('admin.rate-imports.create')->with('status', 'geimporteerd');
    }
}
