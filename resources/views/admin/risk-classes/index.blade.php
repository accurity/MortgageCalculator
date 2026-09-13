<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tariefklassen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <p class="text-green-600 text-sm" style="margin-bottom:16px">
                        @if (session('status') === 'aangemaakt') Tariefklasse aangemaakt.
                        @elseif (session('status') === 'opgeslagen') Tariefklasse opgeslagen.
                        @elseif (session('status') === 'verwijderd') Tariefklasse verwijderd.
                        @endif
                    </p>
                @endif
                @if (session('fout'))
                    <p class="text-red-600 text-sm" style="margin-bottom:16px">{{ session('fout') }}</p>
                @endif

                <p style="margin-bottom:16px">
                    <a href="{{ route('admin.risk-classes.create') }}"><strong>+ Nieuwe tariefklasse</strong></a>
                </p>

                <table class="tax-years-table">
                    <thead>
                        <tr>
                            <th>Naam</th>
                            <th>Code</th>
                            <th>Max. LTV</th>
                            <th>NHG</th>
                            <th>Volgorde</th>
                            <th>Actief</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($klassen as $klasse)
                            <tr>
                                <td>{{ $klasse->name }}</td>
                                <td>{{ $klasse->code }}</td>
                                <td>{{ $klasse->max_ltv !== null ? number_format($klasse->max_ltv, 1, ',', '.') . '%' : '—' }}</td>
                                <td>{{ $klasse->nhg ? 'Ja' : 'Nee' }}</td>
                                <td>{{ $klasse->sort_order }}</td>
                                <td>{{ $klasse->active ? 'Ja' : 'Nee' }}</td>
                                <td>
                                    <a href="{{ route('admin.risk-classes.edit', $klasse) }}">Bewerken</a>
                                    <form method="post" action="{{ route('admin.risk-classes.destroy', $klasse) }}" style="display:inline" onsubmit="return confirm('Tariefklasse {{ $klasse->name }} verwijderen?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="link-button">Verwijderen</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Nog geen tariefklassen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
