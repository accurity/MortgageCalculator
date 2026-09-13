<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RateSourceRequest;
use App\Models\Lender;
use App\Models\Setting;
use App\Services\Mortgage\Domain\Scraping\ScrapeRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class RateSourceController extends Controller
{
    public function edit(Lender $lender): View
    {
        return view('admin.rate-sources.edit', ['lender' => $lender, 'bron' => $lender->rateSource]);
    }

    public function update(RateSourceRequest $request, Lender $lender): RedirectResponse
    {
        $lender->rateSource()->updateOrCreate([], [
            'url' => $request->string('url')->toString(),
            'table_selector' => $request->string('table_selector')->toString(),
            'column_map' => $request->kolomToewijzing(),
            'scraping_allowed' => $request->boolean('scraping_allowed'),
            'note' => $request->filled('note') ? $request->string('note')->toString() : null,
        ]);

        return redirect()->route('admin.lenders.source.edit', $lender)->with('status', 'opgeslagen');
    }

    public function test(Lender $lender): View
    {
        if ($lender->rateSource === null) {
            return view('admin.rate-sources.edit', ['lender' => $lender, 'bron' => null])
                ->with('fout', 'Stel eerst een bron in voordat je een testrun doet.');
        }

        $userAgent = (string)Setting::get(
            'scraper_user_agent',
            'Mozilla/5.0 (compatible; AccurityRatesBot/1.0; +https://mortgagecalculator.accurity.nl)'
        );
        $testResultaat = (new ScrapeRunner($userAgent))->run($lender, dryRun: true);

        return view('admin.rate-sources.edit', [
            'lender' => $lender,
            'bron' => $lender->rateSource,
            'testResultaat' => $testResultaat,
        ]);
    }
}
