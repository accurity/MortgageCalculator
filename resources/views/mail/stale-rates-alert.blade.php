Verouderde tarieven (langer dan 36 uur oud):

@foreach ($verouderd as $rij)
- {{ $rij['naam'] }}: {{ number_format($rij['leeftijdUren'], 1, ',', '.') }} uur oud
@endforeach

Controleer de scrape-status in /admin/rates/status.
