@php($a = $application)
<x-mail::message>
# Bitte bestätigen Sie das Beitrittsgesuch

Guten Tag {{ $a->guardian_name }}

{{ $a->first_name }} {{ $a->last_name }} möchte Mitglied im Verein Treffpunkt Bettingen werden und hat Sie als erziehungsberechtigte Person angegeben.

<x-mail::panel>
**Mitgliedschaft:** {{ $a->typeInfo()->label() }}<br>
**Geburtsdatum:** {{ $a->birthdate?->format('d.m.Y') }}<br>
**Jahresbeitrag:** CHF {{ $a->fee }}
</x-mail::panel>

Mit Ihrer Bestätigung ersetzen Sie die Unterschrift auf dem Papierformular.

<x-mail::button :url="$link">
Gesuch bestätigen
</x-mail::button>

Der Link ist 30 Tage gültig. Falls Sie nichts davon wissen, können Sie diese E-Mail einfach ignorieren – das Gesuch wird dann nicht weiterbearbeitet.

Freundliche Grüsse<br>
Verein Treffpunkt Bettingen
</x-mail::message>
