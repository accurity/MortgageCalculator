<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FixedPeriod;
use App\Models\RiskClass;
use Illuminate\Foundation\Http\FormRequest;

/** Valideert de rekeninstellingen: NHG-grens, IO-opslag, terugvalgemiddelden en het alarm-e-mailadres. */
final class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fallback = $this->input('fallback', []);
        foreach ($fallback as $klasseCode => $rij) {
            foreach ($rij as $periodeId => $waarde) {
                $fallback[$klasseCode][$periodeId] = is_string($waarde) ? str_replace(',', '.', $waarde) : $waarde;
            }
        }
        $this->merge([
            'nhg_grens' => is_string($this->input('nhg_grens')) ? str_replace(['.', ','], ['', '.'], $this->input('nhg_grens')) : $this->input('nhg_grens'),
            'io_surcharge_pct' => is_string($this->input('io_surcharge_pct')) ? str_replace(',', '.', $this->input('io_surcharge_pct')) : $this->input('io_surcharge_pct'),
            'io_max_share_pct' => is_string($this->input('io_max_share_pct')) ? str_replace(',', '.', $this->input('io_max_share_pct')) : $this->input('io_max_share_pct'),
            'fallback' => $fallback,
        ]);
    }

    public function rules(): array
    {
        $regels = [
            'nhg_grens' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'io_surcharge_pct' => ['required', 'numeric', 'min:0', 'max:10'],
            'io_max_share_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'alarm_email' => ['nullable', 'email', 'max:255'],
        ];

        foreach (RiskClass::query()->pluck('code') as $code) {
            foreach (FixedPeriod::query()->pluck('years') as $jaren) {
                $regels["fallback.$code.$jaren"] = ['required', 'numeric', 'min:0', 'max:15'];
            }
        }

        return $regels;
    }

    /** @return array<string, array<int, float>> klassecode => jaren => percentage */
    public function fallbackRates(): array
    {
        $uit = [];
        foreach ((array)$this->validated('fallback', []) as $code => $rij) {
            foreach ($rij as $jaren => $waarde) {
                $uit[$code][(int)$jaren] = (float)$waarde;
            }
        }

        return $uit;
    }
}
