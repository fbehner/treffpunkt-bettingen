<x-mail::message>
# Danke für eure Anfrage!

Guten Tag {{ $reservation->first_name }} {{ $reservation->last_name }}

Wir haben eure Reservationsanfrage erhalten.

@include('mail.partials.reservation')

**Die Reservation ist noch nicht verbindlich.** Wir prüfen die Anfrage und melden uns per E-Mail – Mitglieder haben bei gleichzeitigen Anfragen Vorrang.

Herzliche Grüsse<br>
Verein Treffpunkt Bettingen
</x-mail::message>
