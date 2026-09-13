<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Valideert een belastingjaar. De schijven komen binnen als tekst (één per
 * regel, "bovengrens:tarief", de laatste regel zonder bovengrens voor de
 * open topschijf) zodat er geen dynamische formuliervelden nodig zijn.
 */
class TaxYearRequest extends FormRequest
{
    /** @var list<array{tot: float|null, tarief: float}>|null */
    private ?array $geparsteSchijven = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jaar' => [
                'required', 'integer', 'min:2000', 'max:2100',
                Rule::unique('tax_years', 'jaar')->ignore($this->route('tax_year')),
            ],
            'schijven_tekst' => ['required', 'string'],
            'max_aftrektarief_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'ewf_percentage_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'ewf_grens' => ['required', 'numeric', 'min:0'],
            'hillen_aandeel_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('schijven_tekst')) {
                return;
            }

            try {
                $this->geparsteSchijven = self::parseSchijven((string) $this->input('schijven_tekst'));
            } catch (\InvalidArgumentException $e) {
                $validator->errors()->add('schijven_tekst', $e->getMessage());
            }
        });
    }

    /**
     * @return list<array{tot: float|null, tarief: float}>
     */
    public static function parseSchijven(string $tekst): array
    {
        $regels = array_values(array_filter(array_map('trim', explode("\n", $tekst)), fn (string $r) => $r !== ''));

        if (count($regels) < 2) {
            throw new \InvalidArgumentException('Minstens twee schijven nodig, de laatste zonder bovengrens.');
        }

        $schijven = [];
        $vorigeGrens = 0.0;

        foreach ($regels as $i => $regel) {
            $isLaatste = $i === count($regels) - 1;
            $delen = explode(':', $regel, 2);
            if (count($delen) !== 2) {
                throw new \InvalidArgumentException("Regel \"$regel\" mist een dubbele punt (bovengrens:tarief).");
            }
            [$totRuw, $tariefRuw] = $delen;
            $totRuw = trim($totRuw);
            $tariefRuw = trim(str_replace(',', '.', $tariefRuw));

            if (!is_numeric($tariefRuw)) {
                throw new \InvalidArgumentException("Ongeldig tarief op regel \"$regel\".");
            }
            $tarief = (float) $tariefRuw;
            if ($tarief < 0 || $tarief > 100) {
                throw new \InvalidArgumentException("Tarief op regel \"$regel\" moet tussen 0 en 100 liggen.");
            }

            if ($totRuw === '' || $totRuw === '-') {
                if (!$isLaatste) {
                    throw new \InvalidArgumentException('Alleen de laatste schijf mag zonder bovengrens (open topschijf).');
                }
                $schijven[] = ['tot' => null, 'tarief' => $tarief / 100];

                continue;
            }

            if ($isLaatste) {
                throw new \InvalidArgumentException('De laatste schijf moet open zijn (geen bovengrens).');
            }

            $totRuw = str_replace(',', '.', $totRuw);
            if (!is_numeric($totRuw)) {
                throw new \InvalidArgumentException("Ongeldige bovengrens op regel \"$regel\".");
            }
            $tot = (float) $totRuw;
            if ($tot <= $vorigeGrens) {
                throw new \InvalidArgumentException("Schijven moeten oplopend zijn: \"$regel\" is niet hoger dan de vorige bovengrens.");
            }
            $vorigeGrens = $tot;
            $schijven[] = ['tot' => $tot, 'tarief' => $tarief / 100];
        }

        return $schijven;
    }

    /**
     * @return list<array{tot: float|null, tarief: float}>
     */
    public function schijven(): array
    {
        return $this->geparsteSchijven ?? self::parseSchijven((string) $this->input('schijven_tekst'));
    }

    /** @return array{jaar:int,schijven:list<array{tot:float|null,tarief:float}>,max_aftrektarief:float,ewf_percentage:float,ewf_grens:float,hillen_aandeel:float} */
    public function toModel(): array
    {
        return [
            'jaar' => (int) $this->input('jaar'),
            'schijven' => $this->schijven(),
            'max_aftrektarief' => ((float) str_replace(',', '.', (string) $this->input('max_aftrektarief_pct'))) / 100,
            'ewf_percentage' => ((float) str_replace(',', '.', (string) $this->input('ewf_percentage_pct'))) / 100,
            'ewf_grens' => (float) str_replace(',', '.', (string) $this->input('ewf_grens')),
            'hillen_aandeel' => ((float) str_replace(',', '.', (string) $this->input('hillen_aandeel_pct'))) / 100,
        ];
    }

    /** Tekstweergave van bestaande schijven, voor het formulier. */
    public static function schijvenNaarTekst(array $schijven): string
    {
        return implode("\n", array_map(
            static fn (array $s) => ($s['tot'] === null ? '' : self::getalTekst((float) $s['tot'])) . ':' . self::getalTekst($s['tarief'] * 100),
            $schijven
        ));
    }

    private static function getalTekst(float $n): string
    {
        return rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');
    }
}
