<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'apply_url' => ['nullable', 'url', 'max:2048'],
            'afm_number' => ['nullable', 'string', 'max:50'],
            'active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'delta' => ['required', 'numeric', 'min:-10', 'max:10'],
            'surcharge_ann' => ['nullable', 'numeric', 'min:-10', 'max:10'],
            'surcharge_lin' => ['nullable', 'numeric', 'min:-10', 'max:10'],
            'surcharge_av' => ['nullable', 'numeric', 'min:-10', 'max:10'],
            'logo' => ['nullable', 'file', 'mimes:png,svg,webp', 'max:512'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
        ];

        // De slug is alleen invoer bij het aanmaken; bij bewerken staat hij vast.
        if (!$this->route('lender')) {
            $rules['slug'] = [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('lenders', 'slug'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Alleen kleine letters, cijfers en koppeltekens.',
        ];
    }
}
