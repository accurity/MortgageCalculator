<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Voorbeeld van de import</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p style="margin-bottom:16px">
                    {{ count($rijen) }} regel(s) geldig bevonden, verdeeld over {{ collect($rijen)->pluck('lender_id')->unique()->count() }} verstrekker(s).
                    Elke betrokken verstrekker krijgt hiermee in één keer een nieuwe actuele tariefset; de vorige blijft bewaard als geschiedenis.
                </p>

                <table class="tax-years-table" style="margin-bottom:24px">
                    <thead>
                        <tr>
                            <th>Verstrekker</th>
                            <th>Periode</th>
                            <th>Tariefklasse</th>
                            <th>Rente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rijen as $rij)
                            <tr>
                                <td>{{ $rij['lender_naam'] }}</td>
                                <td>{{ $rij['periode_jaren'] }} jaar</td>
                                <td>{{ $rij['klasse_naam'] }}</td>
                                <td>{{ number_format($rij['percentage'], 2, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <form method="post" action="{{ route('admin.rate-imports.confirm') }}">
                    @csrf
                    <input type="hidden" name="payload" value="{{ $payload }}">
                    <div class="flex items-center gap-4">
                        <x-primary-button>Bevestigen en importeren</x-primary-button>
                        <a href="{{ route('admin.rate-imports.create') }}">Annuleren</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
