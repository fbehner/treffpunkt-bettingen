<x-mail::message>
# Neues Konto wartet auf Freigabe

**{{ $user->name }}** ({{ $user->email }}) hat sich über eine Einladung registriert.

Das Konto hat noch keine Rolle und kommt deshalb weder in die Website-Verwaltung noch ins Backoffice. Bitte weise die passenden Rollen zu:

<x-mail::button :url="url('/admin/users/'.$user->id.'/edit')">
Rollen zuweisen
</x-mail::button>

Falls du die Person nicht kennst, deaktiviere oder lösche das Konto.
</x-mail::message>
