@php
    /** @var \App\Models\FixedPeriod|null $periode */
    $periode ??= null;
@endphp

@if ($periode === null)
    <div>
        <x-input-label for="years" value="Jaren" />
        <x-text-input id="years" name="years" type="number" class="mt-1 block w-full" :value="old('years')" required autofocus />
        <x-input-error :messages="$errors->get('years')" class="mt-2" />
    </div>
@else
    <div>
        <x-input-label for="years" value="Jaren" />
        <x-text-input id="years" type="number" class="mt-1 block w-full" value="{{ $periode->years }}" disabled />
    </div>
@endif

<div class="mt-4">
    <x-input-label for="sort_order" value="Volgorde" />
    <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full" :value="old('sort_order', $periode->sort_order ?? 0)" required />
    <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
</div>

<div class="mt-4">
    <label>
        <input type="hidden" name="active" value="0">
        <input type="checkbox" name="active" value="1" @checked(old('active', $periode->active ?? true))>
        Actief
    </label>
    <x-input-error :messages="$errors->get('active')" class="mt-2" />
</div>

<div class="flex items-center gap-4 mt-4">
    <x-primary-button>Opslaan</x-primary-button>
    <a href="{{ route('admin.fixed-periods.index') }}">Annuleren</a>
</div>
