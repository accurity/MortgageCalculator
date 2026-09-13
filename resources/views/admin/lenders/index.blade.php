<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Verstrekkers</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <p class="text-green-600 text-sm" style="margin-bottom:16px">
                        @if (session('status') === 'aangemaakt') Verstrekker aangemaakt.
                        @elseif (session('status') === 'opgeslagen') Verstrekker opgeslagen.
                        @elseif (session('status') === 'verwijderd') Verstrekker verwijderd.
                        @endif
                    </p>
                @endif

                <p style="margin-bottom:16px">
                    <a href="{{ route('admin.lenders.create') }}"><strong>+ Nieuwe verstrekker</strong></a>
                </p>

                <table class="tax-years-table">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Naam</th>
                            <th>Slug</th>
                            <th>Volgorde</th>
                            <th>Delta</th>
                            <th>Actief</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($verstrekkers as $verstrekker)
                            <tr>
                                <td>
                                    @if ($verstrekker->logoUrl())
                                        <img src="{{ $verstrekker->logoUrl() }}" alt="" style="width:28px;height:28px;object-fit:contain">
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $verstrekker->name }}</td>
                                <td>{{ $verstrekker->slug }}</td>
                                <td>{{ $verstrekker->sort_order }}</td>
                                <td>{{ number_format($verstrekker->delta, 2, ',', '.') }}</td>
                                <td>{{ $verstrekker->active ? 'Ja' : 'Nee' }}</td>
                                <td>
                                    <a href="{{ route('admin.lenders.rate-sets.index', $verstrekker) }}">Tarieven</a>
                                    <a href="{{ route('admin.lenders.source.edit', $verstrekker) }}">Bron</a>
                                    <a href="{{ route('admin.lenders.edit', $verstrekker) }}">Bewerken</a>
                                    <form method="post" action="{{ route('admin.lenders.destroy', $verstrekker) }}" style="display:inline" onsubmit="return confirm('Verstrekker {{ $verstrekker->name }} verwijderen?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="link-button">Verwijderen</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Nog geen verstrekkers.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
