@php($a = $application)
<x-mail::message>
# Neues Beitrittsgesuch

<x-mail::table>
| | |
|:--|:--|
| Mitgliedschaft | {{ $a->typeInfo()->label() }} (CHF {{ $a->fee }}) |
@if ($a->organisation)
| Verein / Institution | {{ $a->organisation }} |
@endif
| Name | {{ $a->displayName() }} |
| Adresse | {{ $a->street }}, {{ $a->zip_city }} |
| E-Mail | {{ $a->email }} |
@if ($a->phone)
| Telefon | {{ $a->phone }} |
@endif
@if ($a->donation)
| Gönnerbeitrag | CHF {{ $a->donation }} |
@endif
@if ($a->guardian_confirmed_at)
| Bestätigt von | {{ $a->guardian_name }} am {{ $a->guardian_confirmed_at->format('d.m.Y') }} |
@endif
</x-mail::table>

@if ($a->message)
**Mitteilung:**<br>
{{ $a->message }}
@endif

<x-mail::button :url="url('/backoffice/membership-applications/'.$a->id.'/edit')">
Im Backoffice öffnen
</x-mail::button>
</x-mail::message>
