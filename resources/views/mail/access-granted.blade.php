<x-mail::message>
# Dein Zugang ist freigeschaltet

Hallo {{ $user->name }}

Du kannst dich jetzt mit deiner E-Mail-Adresse und deinem Passwort anmelden.

@if ($user->hasRole('admin') || $user->hasRole('redaktion'))
<x-mail::button :url="url('/admin')">
Website-Verwaltung öffnen
</x-mail::button>
@endif
@if ($user->hasRole('mitglieder') || $user->hasRole('reservationen'))
<x-mail::button :url="url('/backoffice')">
Backoffice öffnen
</x-mail::button>
@endif

Deine Aufgaben: {{ $user->roleLabels() }}

Herzliche Grüsse<br>
Verein Treffpunkt Bettingen
</x-mail::message>
