<x-mail::message>
# Reservation storniert

Guten Tag {{ $reservation->first_name }} {{ $reservation->last_name }}

Diese Reservation ist storniert:

@include('mail.partials.reservation')

@if ($reservation->decision_note)
{{ $reservation->decision_note }}

@endif
Bei Fragen antwortet einfach auf diese E-Mail oder schreibt an {{ \App\Models\Setting::reservationEmail() }}.

Herzliche Grüsse<br>
Verein Treffpunkt Bettingen
</x-mail::message>
