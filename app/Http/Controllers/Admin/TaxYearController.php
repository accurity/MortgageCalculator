<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaxYearRequest;
use App\Models\TaxYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TaxYearController extends Controller
{
    public function index(): View
    {
        return view('admin.tax-years.index', [
            'jaren' => TaxYear::query()->orderByDesc('jaar')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.tax-years.create');
    }

    public function store(TaxYearRequest $request): RedirectResponse
    {
        TaxYear::query()->create($request->toModel());

        return redirect()->route('admin.tax-years.index')->with('status', 'aangemaakt');
    }

    public function edit(TaxYear $tax_year): View
    {
        return view('admin.tax-years.edit', ['jaar' => $tax_year]);
    }

    public function update(TaxYearRequest $request, TaxYear $tax_year): RedirectResponse
    {
        $tax_year->update($request->toModel());

        return redirect()->route('admin.tax-years.index')->with('status', 'opgeslagen');
    }

    public function destroy(TaxYear $tax_year): RedirectResponse
    {
        $tax_year->delete();

        return redirect()->route('admin.tax-years.index')->with('status', 'verwijderd');
    }
}
