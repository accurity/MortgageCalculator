<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RateSetRequest;
use App\Models\FixedPeriod;
use App\Models\Lender;
use App\Models\RiskClass;
use App\Services\Mortgage\Domain\RateRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class RateSetController extends Controller
{
    public function index(Lender $lender): View
    {
        $sets = $lender->rateSets()
            ->with(['rates.fixedPeriod', 'rates.riskClass'])
            ->orderByDesc('id')
            ->get();

        return view('admin.rate-sets.index', ['lender' => $lender, 'sets' => $sets]);
    }

    public function create(Lender $lender): View
    {
        return view('admin.rate-sets.create', [
            'lender' => $lender,
            'periodes' => FixedPeriod::query()->where('active', true)->orderBy('sort_order')->get(),
            'klassen' => RiskClass::query()->where('active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(RateSetRequest $request, Lender $lender): RedirectResponse
    {
        DB::transaction(function () use ($request, $lender): void {
            $lender->rateSets()->update(['is_current' => false]);
            $set = $lender->rateSets()->create([
                'is_current' => true,
                'note' => $request->string('note')->toString() ?: null,
            ]);
            $set->rates()->createMany($request->tarieven());
        });

        RateRepository::verversCache();

        return redirect()->route('admin.lenders.rate-sets.index', $lender)->with('status', 'aangemaakt');
    }
}
