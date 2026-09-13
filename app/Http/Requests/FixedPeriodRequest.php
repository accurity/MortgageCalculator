<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Valideert een rentevaste periode. De jaren zijn onveranderlijk na aanmaken. */
final class FixedPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regels = [
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'active' => ['boolean'],
        ];

        if (!$this->route('fixed_period')) {
            $regels['years'] = ['required', 'integer', 'min:1', 'max:50', Rule::unique('fixed_periods', 'years')];
        }

        return $regels;
    }
}
