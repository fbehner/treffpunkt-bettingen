<x-mail::message>
# Eure Reservation ist bestätigt

Guten Tag {{ $reservation->first_name }} {{ $reservation->last_name }}

Wir freuen uns, dass ihr den Treffpunkt nutzt. Diese Reservation gilt:

@include('mail.partials.reservation')

@if ($reservation->decision_note)
{{ $reservation->decision_note }}

@endif
Bitte gebt die Räume so zurück, wie ihr sie vorgefunden habt. Falls ihr die Reservation nicht mehr braucht, gebt uns bitte rechtzeitig Bescheid – antwortet einfach auf diese E-Mail oder schreibt an {{ \App\Models\Setting::reservationEmail() }}.

Herzliche Grüsse<br>
Verein Treffpunkt Bettingen
</x-mail::message>
