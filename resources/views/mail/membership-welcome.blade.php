@php
    $m = $membership;
    $contact = $m->contact();
    $statutes = \App\Support\Ui::file(\App\Models\Setting::get('statutes_file'));
    // Gönnerbeitrag: aus dem Gesuch, sonst aus dem ersten Jahresbeitrag
    $donation = (int) ($m->application?->donation ?: $m->fees()->reorder()->orderBy('year')->value('donation'));
@endphp
<x-mail::message>
# Willkommen im Verein!

Guten Tag {{ $m->organisation ? $contact?->fullName() : $m->people->whereIn('relation', ['haupt', 'partner'])->map->fullName()->join(' und ') }}

Der Vorstand hat {{ $m->organisation ? 'die Mitgliedschaft von '.$m->organisation : 'euer Beitrittsgesuch' }} angenommen. Herzlich willkommen beim Verein Treffpunkt Bettingen!

<x-mail::panel>
**Mitgliedernummer:** {{ $m->number }}<br>
**Mitgliedschaft:** {{ $m->typeInfo()->label() }}<br>
**Mitglied seit:** {{ $m->joined_at?->format('d.m.Y') }}<br>
**Jahresbeitrag:** CHF {{ $m->fee }}@if ($donation > 0)<br>
**Gönnerbeitrag:** CHF {{ $donation }}@endif
</x-mail::panel>

@if ($donation > 0)
**Herzlichen Dank für euren Gönnerbeitrag von CHF {{ $donation }}!** Damit helft ihr ganz direkt, den Treffpunkt für Bettingen möglich zu machen.

@endif

Der Jahresbeitrag wird jeweils per 1. Januar fällig. Die Rechnung erhaltet ihr separat.

Als Mitglieder nutzt ihr die Räume des Treffpunkts zu vergünstigten Tarifen und habt Vorrang bei Reservationen. An der Generalversammlung sind alle Mitglieder ab 16 Jahren stimmberechtigt – die Einladung folgt rechtzeitig.

@if ($statutes)
<x-mail::button :url="$statutes">
Statuten ansehen
</x-mail::button>
@endif

Bitte meldet uns Änderungen eurer Adresse oder E-Mail an {{ \App\Models\Setting::get('contact_email') }}.

Herzliche Grüsse<br>
Verein Treffpunkt Bettingen
</x-mail::message>
