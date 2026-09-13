<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\RiskClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valideert een tariefklasse. De code (gebruikt als sleutel in de tarieventabel
 * en de terugvalgemiddelden) wordt bij het aanmaken uit de naam afgeleid en
 * daarna niet meer gewijzigd, net als de slug van een verstrekker.
 */
final class RiskClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $klasse = $this->route('risk_class');

        return [
            'name' => ['required', 'string', 'max:255'],
            'max_ltv' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'nhg' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->boolean('nhg')) {
                return;
            }

            $bestaatAl = RiskClass::query()
                ->where('nhg', true)
                ->when($this->route('risk_class'), fn ($q, $klasse) => $q->whereKeyNot($klasse->id))
                ->exists();

            if ($bestaatAl) {
                $validator->errors()->add('nhg', 'Er is al een andere klasse met de NHG-vlag; er kan er maar één zijn.');
            }
        });
    }
}
