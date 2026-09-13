<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Scrape-status</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p style="margin-bottom:16px">Verstrekkers met een scrape-bron. Boven de {{ $drempelUren }} uur wordt de leeftijd van de actuele tariefset rood gemarkeerd en gaat er een e-mail naar de beheerder (hoogstens één per dag per verstrekker).</p>

                <table class="tax-years-table">
                    <thead>
                        <tr>
                            <th>Verstrekker</th>
                            <th>Scrapen toegestaan</th>
                            <th>Laatste run</th>
                            <th>Resultaat</th>
                            <th>Leeftijd actuele set</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rijen as $rij)
                            <tr>
                                <td>{{ $rij['lender']->name }}</td>
                                <td>{{ $rij['lender']->rateSource->scraping_allowed ? 'Ja' : 'Nee' }}</td>
                                <td>
                                    @if ($rij['laatsteRun'])
                                        {{ $rij['laatsteRun']->created_at->format('d-m-Y H:i') }}
                                        @if ($rij['laatsteRun']->is_dry_run) (dry-run) @endif
                                    @else
                                        Nog niet gedraaid
                                    @endif
                                </td>
                                <td>
                                    @if ($rij['laatsteRun'])
                                        <span style="{{ $rij['laatsteRun']->status === 'failed' ? 'color:#b91c1c' : 'color:#16a34a' }}">{{ $rij['laatsteRun']->status === 'failed' ? 'Mislukt' : 'OK' }}</span>
                                        — {{ $rij['laatsteRun']->message }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="{{ $rij['verouderd'] ? 'color:#b91c1c;font-weight:600' : '' }}">
                                    @if ($rij['leeftijdUren'] !== null)
                                        {{ number_format($rij['leeftijdUren'], 1, ',', '.') }} uur
                                    @else
                                        Geen actuele set
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Nog geen verstrekkers met een scrape-bron.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
