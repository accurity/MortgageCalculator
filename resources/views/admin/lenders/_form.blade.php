@php
    /** @var \App\Models\Lender|null $verstrekker */
    $verstrekker ??= null;
@endphp

@if ($verstrekker === null)
    <div>
        <x-input-label for="slug" value="Slug (kleine letters, cijfers, koppelteken; kan later niet meer wijzigen)" />
        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug')" required autofocus />
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
    </div>
@else
    <div>
        <x-input-label for="slug" value="Slug" />
        <x-text-input id="slug" type="text" class="mt-1 block w-full" value="{{ $verstrekker->slug }}" disabled />
    </div>
@endif

<div class="mt-4">
    <x-input-label for="name" value="Naam" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $verstrekker->name ?? '')" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" value="Omschrijving" />
    <textarea id="description" name="description" rows="3" class="mt-1 block w-full">{{ old('description', $verstrekker->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="apply_url" value="Afsluit-URL" />
    <x-text-input id="apply_url" name="apply_url" type="text" class="mt-1 block w-full" :value="old('apply_url', $verstrekker->apply_url ?? '')" />
    <x-input-error :messages="$errors->get('apply_url')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="afm_number" value="AFM-nummer" />
    <x-text-input id="afm_number" name="afm_number" type="text" class="mt-1 block w-full" :value="old('afm_number', $verstrekker->afm_number ?? '')" />
    <x-input-error :messages="$errors->get('afm_number')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="sort_order" value="Volgorde" />
    <x-text-input id="sort_order" name="sort_order" type="number" class="mt-1 block w-full" :value="old('sort_order', $verstrekker->sort_order ?? 0)" required />
    <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="delta" value="Renteopslag t.o.v. basisrente (procentpunt)" />
    <x-text-input id="delta" name="delta" type="text" class="mt-1 block w-full" :value="old('delta', $verstrekker->delta ?? 0)" required />
    <x-input-error :messages="$errors->get('delta')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="surcharge_ann" value="Opslag annuïtair (procentpunt, leeg = 0)" />
    <x-text-input id="surcharge_ann" name="surcharge_ann" type="text" class="mt-1 block w-full" :value="old('surcharge_ann', $verstrekker->surcharge_ann ?? '')" />
    <x-input-error :messages="$errors->get('surcharge_ann')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="surcharge_lin" value="Opslag lineair (procentpunt, leeg = 0)" />
    <x-text-input id="surcharge_lin" name="surcharge_lin" type="text" class="mt-1 block w-full" :value="old('surcharge_lin', $verstrekker->surcharge_lin ?? '')" />
    <x-input-error :messages="$errors->get('surcharge_lin')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="surcharge_av" value="Opslag aflossingsvrij (procentpunt, leeg = de algemene opslag uit de rekeninstellingen)" />
    <x-text-input id="surcharge_av" name="surcharge_av" type="text" class="mt-1 block w-full" :value="old('surcharge_av', $verstrekker->surcharge_av ?? '')" />
    <x-input-error :messages="$errors->get('surcharge_av')" class="mt-2" />
</div>

<div class="mt-4">
    <label>
        <input type="hidden" name="active" value="0">
        <input type="checkbox" name="active" value="1" @checked(old('active', $verstrekker->active ?? true))>
        Actief
    </label>
    <x-input-error :messages="$errors->get('active')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="logo" value="Logo uploaden (png/svg/webp, max 512 kB)" />
    <input id="logo" name="logo" type="file" class="mt-1 block w-full" accept=".png,.svg,.webp">
    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
    @if ($verstrekker && $verstrekker->logoUrl())
        <p style="margin-top:8px"><img src="{{ $verstrekker->logoUrl() }}" alt="" style="width:40px;height:40px;object-fit:contain"></p>
    @endif
</div>

<div class="mt-4">
    <x-input-label for="logo_url" value="Of: logo via externe URL" />
    <x-text-input id="logo_url" name="logo_url" type="text" class="mt-1 block w-full" :value="old('logo_url', $verstrekker->logo_url ?? '')" />
    <x-input-error :messages="$errors->get('logo_url')" class="mt-2" />
</div>

<div class="flex items-center gap-4 mt-4">
    <x-primary-button>Opslaan</x-primary-button>
    <a href="{{ route('admin.lenders.index') }}">Annuleren</a>
</div>
