@php($r = $reservation)
<x-mail::panel>
**Raum:** {{ $r->packageInfo()->label() }}<br>
**Zeit:** {{ $r->timeLabel() }}<br>
**Anlass:** {{ $r->purpose }}@if ($r->participants) ({{ $r->participants }} Personen)@endif<br>
@if ($r->public_title)
**Im öffentlichen Kalender:** {{ $r->public_title }}<br>
@endif
**Voraussichtlicher Preis:** @if ($r->price) CHF {{ $r->price }} ({{ $r->is_member ? 'Mitgliedertarif' : 'Tarif für Nichtmitglieder' }})@else nach Absprache @endif
</x-mail::panel>
