<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valideert de scrape-bron van een verstrekker. De kolomtoewijzing komt
 * binnen als tekst ("periode,klasse,nhg,rente") zodat het aantal kolommen
 * niet vooraf vastligt.
 */
final class RateSourceRequest extends FormRequest
{
    private const TOEGESTANE_ROLLEN = ['periode', 'klasse', 'nhg', 'rente', 'negeren'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'table_selector' => ['required', 'string', 'max:255'],
            'column_map' => ['required', 'string', 'max:255'],
            'scraping_allowed' => ['boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rollen = $this->kolomToewijzing();

            foreach ($rollen as $rol) {
                if (!in_array($rol, self::TOEGESTANE_ROLLEN, true)) {
                    $validator->errors()->add('column_map', "Onbekende rol \"$rol\": kolomtoewijzing verwacht per kolom één van de woorden periode, klasse, nhg, rente of negeren — niet de waardes uit de tabel zelf.");

                    return;
                }
            }

            if (!in_array('periode', $rollen, true)) {
                $validator->errors()->add('column_map', 'Er moet een kolom met rol "periode" zijn.');
            }
            if (!in_array('rente', $rollen, true)) {
                $validator->errors()->add('column_map', 'Er moet een kolom met rol "rente" zijn.');
            }
            if (!in_array('klasse', $rollen, true) && !in_array('nhg', $rollen, true)) {
                $validator->errors()->add('column_map', 'Er moet een kolom met rol "klasse" of "nhg" zijn.');
            }
        });
    }

    /** @return list<string> */
    public function kolomToewijzing(): array
    {
        return array_map(
            static fn (string $r): string => strtolower(trim($r)),
            explode(',', (string)$this->input('column_map'))
        );
    }
}
