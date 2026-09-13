<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RiskClassRequest;
use App\Models\Rate;
use App\Models\RiskClass;
use App\Services\Mortgage\Domain\RateRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class RiskClassController extends Controller
{
    public function index(): View
    {
        return view('admin.risk-classes.index', [
            'klassen' => RiskClass::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.risk-classes.create');
    }

    public function store(RiskClassRequest $request): RedirectResponse
    {
        $klasse = new RiskClass($request->safe()->except(['nhg', 'active', 'max_ltv']));
        $klasse->code = Str::slug($request->string('name')->toString(), '_');
        $klasse->max_ltv = $request->filled('max_ltv') ? (float)$request->input('max_ltv') : null;
        $klasse->nhg = $request->boolean('nhg');
        $klasse->active = $request->boolean('active');
        $klasse->save();

        RateRepository::verversCache();

        return redirect()->route('admin.risk-classes.index')->with('status', 'aangemaakt');
    }

    public function edit(RiskClass $riskClass): View
    {
        return view('admin.risk-classes.edit', ['klasse' => $riskClass]);
    }

    public function update(RiskClassRequest $request, RiskClass $riskClass): RedirectResponse
    {
        $riskClass->fill($request->safe()->except(['nhg', 'active', 'max_ltv']));
        $riskClass->max_ltv = $request->filled('max_ltv') ? (float)$request->input('max_ltv') : null;
        $riskClass->nhg = $request->boolean('nhg');
        $riskClass->active = $request->boolean('active');
        $riskClass->save();

        RateRepository::verversCache();

        return redirect()->route('admin.risk-classes.index')->with('status', 'opgeslagen');
    }

    public function destroy(RiskClass $riskClass): RedirectResponse
    {
        if (Rate::query()->where('risk_class_id', $riskClass->id)->exists()) {
            return redirect()->route('admin.risk-classes.index')
                ->with('fout', "Klasse \"{$riskClass->name}\" wordt gebruikt in een tariefset en kan niet worden verwijderd. Zet 'm op inactief.");
        }

        $riskClass->delete();
        RateRepository::verversCache();

        return redirect()->route('admin.risk-classes.index')->with('status', 'verwijderd');
    }
}
