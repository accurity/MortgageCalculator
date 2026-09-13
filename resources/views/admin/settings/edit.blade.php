<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekeninstellingen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status') === 'opgeslagen')
                    <p class="text-green-600 text-sm" style="margin-bottom:16px">Instellingen opgeslagen.</p>
                @endif

                <form method="post" action="{{ route('admin.settings.update') }}">
                    @csrf
                    @method('put')

                    <div class="max-w-xl">
                        <x-input-label for="nhg_grens" value="NHG-grens (woningwaarde in euro)" />
                        <x-text-input id="nhg_grens" name="nhg_grens" type="text" class="mt-1 block w-full" :value="old('nhg_grens', $nhgGrens)" required />
                        <x-input-error :messages="$errors->get('nhg_grens')" class="mt-2" />
                    </div>

                    <div class="mt-4 max-w-xl">
                        <x-input-label for="io_surcharge_pct" value="Renteopslag op een aflossingsvrij deel (procentpunt)" />
                        <x-text-input id="io_surcharge_pct" name="io_surcharge_pct" type="text" class="mt-1 block w-full" :value="old('io_surcharge_pct', $ioSurchargePct)" required />
                        <x-input-error :messages="$errors->get('io_surcharge_pct')" class="mt-2" />
                    </div>

                    <div class="mt-4 max-w-xl">
                        <x-input-label for="io_max_share_pct" value="Maximaal aflossingsvrij aandeel van de woningwaarde (%)" />
                        <x-text-input id="io_max_share_pct" name="io_max_share_pct" type="text" class="mt-1 block w-full" :value="old('io_max_share_pct', $ioMaxSharePct)" required />
                        <x-input-error :messages="$errors->get('io_max_share_pct')" class="mt-2" />
                    </div>

                    <div class="mt-4 max-w-xl">
                        <x-input-label for="alarm_email" value="E-mailadres voor meldingen (bijv. verouderde tarieven)" />
                        <x-text-input id="alarm_email" name="alarm_email" type="text" class="mt-1 block w-full" :value="old('alarm_email', $alarmEmail)" />
                        <x-input-error :messages="$errors->get('alarm_email')" class="mt-2" />
                    </div>

                    <h3 style="margin-top:32px;margin-bottom:8px;font-weight:600">Terugvalgemiddelden</h3>
                    <p style="margin-bottom:16px">Gebruikt per periode en tariefklasse zolang er geen actuele tariefset ligt.</p>

                    <table class="tax-years-table">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                @foreach ($klassen as $klasse)
                                    <th>{{ $klasse->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($periodes as $periode)
                                <tr>
                                    <td>{{ $periode->years }} jaar</td>
                                    @foreach ($klassen as $klasse)
                                        <td>
                                            <x-text-input type="text" name="fallback[{{ $klasse->code }}][{{ $periode->years }}]" class="block w-full" :value="old('fallback.' . $klasse->code . '.' . $periode->years, $fallback[$klasse->code][$periode->years] ?? '')" required />
                                            <x-input-error :messages="$errors->get('fallback.' . $klasse->code . '.' . $periode->years)" class="mt-1" />
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="flex items-center gap-4 mt-4">
                        <x-primary-button>Opslaan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
