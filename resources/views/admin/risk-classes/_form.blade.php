@php
    /** @var \App\Models\RiskClass|null $klasse */
    $klasse ??= null;
@endphp

<div>
    <x-input-label for="name" value="Naam" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $klasse->name ?? '')" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

@if ($klasse !== null)
    <div class="mt-4">
        <x-input-label for="code" value="Code" />
        <x-text-input id="code" type="text" class="mt-1 block w-full" value="{{ $klasse->code }}" disabled />
    </div>
@endif

<div class="mt-4">
    <x-input-label for="max_ltv" value="Bovengrens LTV (%), leeg voor NHG" />
    <x-text-input id="max_ltv" name="max_ltv" type="text" class="mt-1 block w-full" :value="old('max_ltv', $klasse->max_ltv ?? '')" />
    <x-input-error :messages="$errors->get('max_ltv')" class="mt-2" />
</div>

<div class="mt-4">
    <label>
        <input type="hidden" name="nhg" value="0">
        <input type="checkbox" name="nhg" value="1" @checked(old('nhg', $klasse->nhg ?? false))>
        NHG-klasse
    </label>
    <x-input-error :messages="$errors->get('nhg')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="sort_order" value="Volgorde" />
    <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full" :value="old('sort_order', $klasse->sort_order ?? 0)" required />
    <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
</div>

<div class="mt-4">
    <label>
        <input type="hidden" name="active" value="0">
        <input type="checkbox" name="active" value="1" @checked(old('active', $klasse->active ?? true))>
        Actief
    </label>
    <x-input-error :messages="$errors->get('active')" class="mt-2" />
</div>

<div class="flex items-center gap-4 mt-4">
    <x-primary-button>Opslaan</x-primary-button>
    <a href="{{ route('admin.risk-classes.index') }}">Annuleren</a>
</div>
