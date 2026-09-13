<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\FixedPeriod;
use App\Models\RiskClass;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valideert een volledige tariefset: een percentage voor elke combinatie van
 * periode en tariefklasse. "Volledig" is bewust verplicht (geen gedeeltelijke
 * sets) zodat een nieuwe set altijd in één keer een bruikbaar geheel is.
 */
final class RateSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rates = $this->input('rates', []);
        foreach ($rates as $periodeId => $rij) {
            foreach ($rij as $klasseId => $waarde) {
                $rates[$periodeId][$klasseId] = is_string($waarde) ? str_replace(',', '.', $waarde) : $waarde;
            }
        }
        $this->merge(['rates' => $rates]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $regels = ['note' => ['nullable', 'string', 'max:500']];

        foreach (FixedPeriod::query()->pluck('id') as $periodeId) {
            foreach (RiskClass::query()->pluck('id') as $klasseId) {
                $regels["rates.$periodeId.$klasseId"] = ['required', 'numeric', 'min:0', 'max:15'];
            }
        }

        return $regels;
    }

    /** @return list<array{fixed_period_id: int, risk_class_id: int, percentage: float}> */
    public function tarieven(): array
    {
        $uit = [];
        foreach ((array)$this->validated('rates', []) as $periodeId => $rij) {
            foreach ($rij as $klasseId => $waarde) {
                $uit[] = [
                    'fixed_period_id' => (int)$periodeId,
                    'risk_class_id' => (int)$klasseId,
                    'percentage' => (float)$waarde,
                ];
            }
        }

        return $uit;
    }
}
