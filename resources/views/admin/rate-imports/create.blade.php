<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tarieven importeren (CSV)</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status') === 'geimporteerd')
                    <p class="text-green-600 text-sm" style="margin-bottom:16px">Import geslaagd; de nieuwe tarieven zijn direct actueel.</p>
                @endif
                @if (session('fout'))
                    <p class="text-red-600 text-sm" style="margin-bottom:16px">{{ session('fout') }}</p>
                @endif

                <p style="margin-bottom:16px">
                    Kolommen: <code>slug</code> (verstrekker), <code>periode</code> (jaren), <code>klasse</code> (code van de tariefklasse), <code>nhg</code> (ja/nee — overschrijft klasse), <code>rente</code> (percentage, punt of komma).
                </p>

                @if (!empty($fouten))
                    <div style="margin-bottom:16px;color:#b91c1c">
                        <strong>Het bestand bevat fouten; er is niets geïmporteerd:</strong>
                        <ul style="margin-top:6px;padding-left:20px;list-style:disc">
                            @foreach ($fouten as $fout)
                                <li>{{ $fout }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('admin.rate-imports.store') }}" enctype="multipart/form-data" class="max-w-xl">
                    @csrf
                    <x-input-label for="file" value="CSV-bestand" />
                    <input id="file" name="file" type="file" accept=".csv,.txt" class="mt-1 block w-full" required>
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />

                    <div class="flex items-center gap-4 mt-4">
                        <x-primary-button>Voorbeeld tonen</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
