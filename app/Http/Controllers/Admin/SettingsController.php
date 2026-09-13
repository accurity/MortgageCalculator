<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\FixedPeriod;
use App\Models\RiskClass;
use App\Models\Setting;
use App\Services\Mortgage\Constants;
use App\Services\Mortgage\Domain\RateRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'nhgGrens' => RateRepository::nhgGrens(),
            'ioSurchargePct' => Constants::ioSurcharge(),
            'ioMaxSharePct' => Constants::ioMaxShare() * 100,
            'alarmEmail' => Setting::get('alarm_email', ''),
            'periodes' => FixedPeriod::query()->where('active', true)->orderBy('sort_order')->get(),
            'klassen' => RiskClass::query()->where('active', true)->orderBy('sort_order')->get(),
            'fallback' => json_decode(Setting::get('fallback_rates', '{}') ?? '{}', true) ?: [],
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        Setting::set('nhg_grens', (string)$request->float('nhg_grens'));
        Setting::set('io_surcharge', (string)($request->float('io_surcharge_pct')));
        Setting::set('io_max_share', (string)($request->float('io_max_share_pct') / 100));
        Setting::set('alarm_email', (string)$request->string('alarm_email'));
        Setting::set('fallback_rates', json_encode($request->fallbackRates(), JSON_THROW_ON_ERROR));

        Constants::verversCache();
        RateRepository::verversCache();

        return redirect()->route('admin.settings.edit')->with('status', 'opgeslagen');
    }
}
