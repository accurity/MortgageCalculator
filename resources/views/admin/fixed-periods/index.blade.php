<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rentevaste periodes</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <p class="text-green-600 text-sm" style="margin-bottom:16px">
                        @if (session('status') === 'aangemaakt') Periode aangemaakt.
                        @elseif (session('status') === 'opgeslagen') Periode opgeslagen.
                        @elseif (session('status') === 'verwijderd') Periode verwijderd.
                        @endif
                    </p>
                @endif
                @if (session('fout'))
                    <p class="text-red-600 text-sm" style="margin-bottom:16px">{{ session('fout') }}</p>
                @endif

                <p style="margin-bottom:16px">
                    <a href="{{ route('admin.fixed-periods.create') }}"><strong>+ Nieuwe periode</strong></a>
                </p>

                <table class="tax-years-table">
                    <thead>
                        <tr>
                            <th>Jaren</th>
                            <th>Volgorde</th>
                            <th>Actief</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periodes as $periode)
                            <tr>
                                <td>{{ $periode->years }}</td>
                                <td>{{ $periode->sort_order }}</td>
                                <td>{{ $periode->active ? 'Ja' : 'Nee' }}</td>
                                <td>
                                    <a href="{{ route('admin.fixed-periods.edit', $periode) }}">Bewerken</a>
                                    <form method="post" action="{{ route('admin.fixed-periods.destroy', $periode) }}" style="display:inline" onsubmit="return confirm('Periode {{ $periode->years }} jaar verwijderen?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="link-button">Verwijderen</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Nog geen periodes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
