@php
    /** @var \App\Models\TaxYear|null $jaar */
    $jaar ??= null;
    $schijvenTekst = old('schijven_tekst', $jaar ? \App\Http\Requests\TaxYearRequest::schijvenNaarTekst($jaar->schijven) : "38883:35.70\n79137:37.56\n:49.50");
@endphp

<div>
    <x-input-label for="jaar" value="Jaar" />
    <x-text-input id="jaar" name="jaar" type="number" class="mt-1 block w-full" :value="old('jaar', $jaar->jaar ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('jaar')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="schijven_tekst" value="Schijven (bovengrens:tarief%, één per regel; de laatste regel zonder bovengrens is de open topschijf)" />
    <textarea id="schijven_tekst" name="schijven_tekst" rows="5" class="mt-1 block w-full" required>{{ $schijvenTekst }}</textarea>
    <x-input-error :messages="$errors->get('schijven_tekst')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="max_aftrektarief_pct" value="Maximaal aftrektarief (%)" />
    <x-text-input id="max_aftrektarief_pct" name="max_aftrektarief_pct" type="text" class="mt-1 block w-full" :value="old('max_aftrektarief_pct', $jaar ? number_format($jaar->max_aftrektarief * 100, 4, '.', '') : '')" required />
    <x-input-error :messages="$errors->get('max_aftrektarief_pct')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="ewf_percentage_pct" value="Eigenwoningforfait (%)" />
    <x-text-input id="ewf_percentage_pct" name="ewf_percentage_pct" type="text" class="mt-1 block w-full" :value="old('ewf_percentage_pct', $jaar ? number_format($jaar->ewf_percentage * 100, 4, '.', '') : '')" required />
    <x-input-error :messages="$errors->get('ewf_percentage_pct')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="ewf_grens" value="EWF-grens (woningwaarde in euro)" />
    <x-text-input id="ewf_grens" name="ewf_grens" type="text" class="mt-1 block w-full" :value="old('ewf_grens', $jaar->ewf_grens ?? '')" required />
    <x-input-error :messages="$errors->get('ewf_grens')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="hillen_aandeel_pct" value="Wet Hillen, aandeel van de aftrek (%)" />
    <x-text-input id="hillen_aandeel_pct" name="hillen_aandeel_pct" type="text" class="mt-1 block w-full" :value="old('hillen_aandeel_pct', $jaar ? number_format($jaar->hillen_aandeel * 100, 4, '.', '') : '')" required />
    <x-input-error :messages="$errors->get('hillen_aandeel_pct')" class="mt-2" />
</div>

<div class="flex items-center gap-4 mt-4">
    <x-primary-button>Opslaan</x-primary-button>
    <a href="{{ route('admin.tax-years.index') }}">Annuleren</a>
</div>
