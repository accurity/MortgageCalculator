<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FixedPeriodRequest;
use App\Models\FixedPeriod;
use App\Models\Rate;
use App\Services\Mortgage\Domain\RateRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class FixedPeriodController extends Controller
{
    public function index(): View
    {
        return view('admin.fixed-periods.index', [
            'periodes' => FixedPeriod::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.fixed-periods.create');
    }

    public function store(FixedPeriodRequest $request): RedirectResponse
    {
        $periode = new FixedPeriod($request->safe()->except('active'));
        $periode->active = $request->boolean('active');
        $periode->save();

        RateRepository::verversCache();

        return redirect()->route('admin.fixed-periods.index')->with('status', 'aangemaakt');
    }

    public function edit(FixedPeriod $fixedPeriod): View
    {
        return view('admin.fixed-periods.edit', ['periode' => $fixedPeriod]);
    }

    public function update(FixedPeriodRequest $request, FixedPeriod $fixedPeriod): RedirectResponse
    {
        $fixedPeriod->fill($request->safe()->except(['active', 'years']));
        $fixedPeriod->active = $request->boolean('active');
        $fixedPeriod->save();

        RateRepository::verversCache();

        return redirect()->route('admin.fixed-periods.index')->with('status', 'opgeslagen');
    }

    public function destroy(FixedPeriod $fixedPeriod): RedirectResponse
    {
        if (Rate::query()->where('fixed_period_id', $fixedPeriod->id)->exists()) {
            return redirect()->route('admin.fixed-periods.index')
                ->with('fout', "Periode \"{$fixedPeriod->years} jaar\" wordt gebruikt in een tariefset en kan niet worden verwijderd. Zet 'm op inactief.");
        }

        $fixedPeriod->delete();
        RateRepository::verversCache();

        return redirect()->route('admin.fixed-periods.index')->with('status', 'verwijderd');
    }
}
