<x-mail::message>
# Willkommen beim Treffpunkt Bettingen

Hallo{{ $invitation->name ? ' '.$invitation->name : '' }}

{{ $invitation->inviter?->name ?? 'Der Verein' }} lädt dich ein, ein Konto für die Verwaltung der Website treffpunkt-bettingen.ch anzulegen.

<x-mail::button :url="$link">
Konto anlegen
</x-mail::button>

Nach der Registrierung schaltet dich ein Webadmin frei und weist dir die passenden Aufgaben zu – du bekommst dann nochmals eine E-Mail.

Der Link ist {{ \App\Models\Invitation::VALID_DAYS }} Tage gültig und funktioniert nur einmal. Falls du diese Einladung nicht erwartet hast, kannst du die E-Mail einfach ignorieren.

Herzliche Grüsse<br>
Verein Treffpunkt Bettingen
</x-mail::message>
