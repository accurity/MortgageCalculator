<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nieuwe tariefset — {{ $lender->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p style="margin-bottom:16px">Vul voor elke combinatie een percentage in. Deze set wordt direct actueel zodra je opslaat; de vorige set blijft bewaard als geschiedenis.</p>

                <form method="post" action="{{ route('admin.lenders.rate-sets.store', $lender) }}">
                    @csrf

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
                                            <x-text-input type="text" name="rates[{{ $periode->id }}][{{ $klasse->id }}]" class="block w-full" :value="old('rates.' . $periode->id . '.' . $klasse->id)" required />
                                            <x-input-error :messages="$errors->get('rates.' . $periode->id . '.' . $klasse->id)" class="mt-1" />
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-4 max-w-xl">
                        <x-input-label for="note" value="Toelichting (optioneel)" />
                        <textarea id="note" name="note" rows="2" class="mt-1 block w-full">{{ old('note') }}</textarea>
                        <x-input-error :messages="$errors->get('note')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 mt-4">
                        <x-primary-button>Opslaan als actuele tariefset</x-primary-button>
                        <a href="{{ route('admin.lenders.rate-sets.index', $lender) }}">Annuleren</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
