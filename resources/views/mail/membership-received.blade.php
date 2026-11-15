@php($a = $application)
<x-mail::message>
# Danke für euer Beitrittsgesuch!

Guten Tag {{ $a->first_name }} {{ $a->last_name }}

Wir haben euer Gesuch um Mitgliedschaft im Verein Treffpunkt Bettingen erhalten.

<x-mail::panel>
**Mitgliedschaft:** {{ $a->typeInfo()->label() }}@if ($a->organisation) – {{ $a->organisation }}@endif<br>
**Jahresbeitrag:** CHF {{ $a->fee }}@if ($a->donation)<br>
**Gönnerbeitrag:** CHF {{ $a->donation }}@endif
</x-mail::panel>

@if ($a->needsGuardian())
Weil die Mitgliedschaft für eine minderjährige Person ist, haben wir {{ $a->guardian_name }} gebeten, das Gesuch per E-Mail zu bestätigen.

@endif
{{ \App\Models\Setting::get('membership_note') }}

Über die Aufnahme entscheidet der Vorstand. Wir melden uns bei euch.

Herzliche Grüsse<br>
Verein Treffpunkt Bettingen
</x-mail::message>
