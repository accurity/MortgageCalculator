<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LenderRequest;
use App\Models\Lender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class LenderController extends Controller
{
    public function index(): View
    {
        return view('admin.lenders.index', [
            'verstrekkers' => Lender::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.lenders.create');
    }

    public function store(LenderRequest $request): RedirectResponse
    {
        $lender = new Lender($request->safe()->except(['logo', 'logo_url', 'active']));
        $lender->slug = $request->string('slug')->toString();
        $lender->active = $request->boolean('active');
        $this->verwerkLogo($request, $lender);
        $lender->save();

        return redirect()->route('admin.lenders.index')->with('status', 'aangemaakt');
    }

    public function edit(Lender $lender): View
    {
        return view('admin.lenders.edit', ['verstrekker' => $lender]);
    }

    public function update(LenderRequest $request, Lender $lender): RedirectResponse
    {
        $lender->fill($request->safe()->except(['logo', 'logo_url', 'slug', 'active']));
        $lender->active = $request->boolean('active');
        $this->verwerkLogo($request, $lender);
        $lender->save();

        return redirect()->route('admin.lenders.index')->with('status', 'opgeslagen');
    }

    public function destroy(Lender $lender): RedirectResponse
    {
        $this->verwijderLogoBestand($lender);
        $lender->delete();

        return redirect()->route('admin.lenders.index')->with('status', 'verwijderd');
    }

    /**
     * Upload komt rechtstreeks in public/logos terecht; geen storage:link
     * nodig. Een nieuwe upload wint van een eerder opgegeven externe URL en
     * andersom: wat er in dit formulier stond, is de nieuwe staat.
     */
    private function verwerkLogo(LenderRequest $request, Lender $lender): void
    {
        if ($request->hasFile('logo')) {
            $this->verwijderLogoBestand($lender);

            $bestand = $request->file('logo');
            $naam = $lender->slug . '-' . Str::random(8) . '.' . $bestand->getClientOriginalExtension();
            if (!is_dir(public_path('logos'))) {
                mkdir(public_path('logos'), 0755, true);
            }
            $bestand->move(public_path('logos'), $naam);

            $lender->logo_path = 'logos/' . $naam;
            $lender->logo_url = null;

            return;
        }

        if ($request->filled('logo_url')) {
            $this->verwijderLogoBestand($lender);
            $lender->logo_path = null;
            $lender->logo_url = $request->input('logo_url');
        }
    }

    private function verwijderLogoBestand(Lender $lender): void
    {
        if ($lender->logo_path !== null) {
            @unlink(public_path($lender->logo_path));
        }
    }
}
