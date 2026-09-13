<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Belastingjaren</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <p class="text-green-600 text-sm" style="margin-bottom:16px">
                        @if (session('status') === 'aangemaakt') Belastingjaar aangemaakt.
                        @elseif (session('status') === 'opgeslagen') Belastingjaar opgeslagen.
                        @elseif (session('status') === 'verwijderd') Belastingjaar verwijderd.
                        @endif
                    </p>
                @endif

                <p style="margin-bottom:16px">
                    <a href="{{ route('admin.tax-years.create') }}"><strong>+ Nieuw belastingjaar</strong></a>
                </p>

                <table class="tax-years-table">
                    <thead>
                        <tr>
                            <th>Jaar</th>
                            <th>Schijven</th>
                            <th>Max. aftrektarief</th>
                            <th>EWF</th>
                            <th>EWF-grens</th>
                            <th>Wet Hillen</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jaren as $jaar)
                            <tr>
                                <td>{{ $jaar->jaar }}</td>
                                <td>
                                    @foreach ($jaar->schijven as $schijf)
                                        {{ $schijf['tot'] === null ? 'boven' : ('t/m € ' . number_format($schijf['tot'], 0, ',', '.')) }}:
                                        {{ number_format($schijf['tarief'] * 100, 2, ',', '.') }}%<br>
                                    @endforeach
                                </td>
                                <td>{{ number_format($jaar->max_aftrektarief * 100, 2, ',', '.') }}%</td>
                                <td>{{ number_format($jaar->ewf_percentage * 100, 2, ',', '.') }}%</td>
                                <td>€ {{ number_format($jaar->ewf_grens, 0, ',', '.') }}</td>
                                <td>{{ number_format($jaar->hillen_aandeel * 100, 2, ',', '.') }}%</td>
                                <td>
                                    <a href="{{ route('admin.tax-years.edit', $jaar) }}">Bewerken</a>
                                    <form method="post" action="{{ route('admin.tax-years.destroy', $jaar) }}" style="display:inline" onsubmit="return confirm('Belastingjaar {{ $jaar->jaar }} verwijderen?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="link-button">Verwijderen</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Nog geen belastingjaren.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
