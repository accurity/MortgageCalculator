<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Scrape-bron — {{ $lender->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status') === 'opgeslagen')
                    <p class="text-green-600 text-sm" style="margin-bottom:16px">Bron opgeslagen.</p>
                @endif
                @if (session('fout') || ($fout ?? null))
                    <p class="text-red-600 text-sm" style="margin-bottom:16px">{{ session('fout') ?? $fout }}</p>
                @endif

                <form method="post" action="{{ route('admin.lenders.source.update', $lender) }}" class="max-w-xl">
                    @csrf
                    @method('put')

                    <x-input-label for="url" value="URL van de tarievenpagina" />
                    <x-text-input id="url" name="url" type="text" class="mt-1 block w-full" :value="old('url', $bron->url ?? '')" required />
                    <x-input-error :messages="$errors->get('url')" class="mt-2" />

                    <div class="mt-4">
                        <x-input-label for="table_selector" value="CSS-selector van de tabel" />
                        <x-text-input id="table_selector" name="table_selector" type="text" class="mt-1 block w-full" :value="old('table_selector', $bron->table_selector ?? '')" required placeholder="table#tarieven" />
                        <x-input-error :messages="$errors->get('table_selector')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="column_map" value="Kolomtoewijzing, op volgorde (periode, klasse, nhg, rente of negeren)" />
                        <x-text-input id="column_map" name="column_map" type="text" class="mt-1 block w-full" :value="old('column_map', $bron ? implode(',', $bron->column_map) : '')" required placeholder="periode,klasse,nhg,rente" />
                        <x-input-error :messages="$errors->get('column_map')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="note" value="Notitie (bijv. toestemming/voorwaarden)" />
                        <textarea id="note" name="note" rows="3" class="mt-1 block w-full">{{ old('note', $bron->note ?? '') }}</textarea>
                        <x-input-error :messages="$errors->get('note')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <label>
                            <input type="hidden" name="scraping_allowed" value="0">
                            <input type="checkbox" name="scraping_allowed" value="1" @checked(old('scraping_allowed', $bron->scraping_allowed ?? false))>
                            Scrapen toegestaan
                        </label>
                        <x-input-error :messages="$errors->get('scraping_allowed')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 mt-4">
                        <x-primary-button>Opslaan</x-primary-button>
                        <a href="{{ route('admin.lenders.index') }}">Terug naar verstrekkers</a>
                    </div>
                </form>

                @if ($bron)
                    <form method="post" action="{{ route('admin.lenders.source.test', $lender) }}" style="margin-top:24px">
                        @csrf
                        <x-primary-button type="submit">Testrun (dry-run, slaat niets op)</x-primary-button>
                    </form>
                @endif

                @isset($testResultaat)
                    <div style="margin-top:24px">
                        @if ($testResultaat->success)
                            <p class="text-green-600 text-sm">{{ $testResultaat->message }}</p>
                            <table class="tax-years-table">
                                <thead>
                                    <tr>
                                        <th>Periode</th>
                                        <th>Tariefklasse</th>
                                        <th>Rente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($testResultaat->rijen as $rij)
                                        <tr>
                                            <td>{{ $rij['periode_jaren'] }} jaar</td>
                                            <td>{{ $rij['klasse_naam'] }}</td>
                                            <td>{{ number_format($rij['percentage'], 2, ',', '.') }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-red-600 text-sm">{{ $testResultaat->message }}</p>
                        @endif
                    </div>
                @endisset
            </div>
        </div>
    </div>
</x-app-layout>
