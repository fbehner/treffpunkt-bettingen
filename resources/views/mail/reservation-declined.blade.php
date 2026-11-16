<x-mail::message>
# Eure Reservationsanfrage

Guten Tag {{ $reservation->first_name }} {{ $reservation->last_name }}

Leider können wir eure Anfrage nicht bestätigen.

@include('mail.partials.reservation')

@if ($reservation->decision_note)
{{ $reservation->decision_note }}

@endif
Im Belegungskalender seht ihr, wann die Räume frei sind:

<x-mail::button :url="route('reservation.create')">
Belegungskalender
</x-mail::button>

Herzliche Grüsse<br>
Verein Treffpunkt Bettingen
</x-mail::message>
