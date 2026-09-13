<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tarieven — {{ $lender->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('status'))
                    <p class="text-green-600 text-sm" style="margin-bottom:16px">
                        @if (session('status') === 'aangemaakt') Nieuwe tariefset opgeslagen en actueel.
                        @endif
                    </p>
                @endif

                <p style="margin-bottom:16px">
                    <a href="{{ route('admin.lenders.rate-sets.create', $lender) }}"><strong>+ Nieuwe tariefset</strong></a>
                    &nbsp;·&nbsp;
                    <a href="{{ route('admin.lenders.index') }}">Terug naar verstrekkers</a>
                </p>

                @forelse ($sets as $set)
                    <table class="tax-years-table" style="margin-bottom:24px">
                        <caption style="text-align:left;padding-bottom:6px">
                            <strong>{{ $set->created_at->format('d-m-Y H:i') }}</strong>
                            @if ($set->is_current) <span style="color:#16a34a">— actueel</span> @endif
                            @if ($set->note) — {{ $set->note }} @endif
                        </caption>
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Tariefklasse</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($set->rates->sortBy([['fixed_period_id','asc'],['risk_class_id','asc']]) as $rate)
                                <tr>
                                    <td>{{ $rate->fixedPeriod->years }} jaar</td>
                                    <td>{{ $rate->riskClass->name }}</td>
                                    <td>{{ number_format($rate->percentage, 2, ',', '.') }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @empty
                    <p>Nog geen tariefsets voor deze verstrekker.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
