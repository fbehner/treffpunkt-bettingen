@php($r = $reservation)
<x-mail::message>
# Neue Reservationsanfrage

@include('mail.partials.reservation')

**Von:** {{ $r->contactName() }}<br>
{{ $r->street }}, {{ $r->zip_city }}<br>
{{ $r->email }} · {{ $r->phone }}<br>
**Mitglied:** {{ $r->is_member ? 'ja'.($r->member_number ? ' (Nr. '.$r->member_number.')' : '').' – bitte prüfen' : 'nein' }}

@if ($r->message)
**Mitteilung:** {{ $r->message }}

@endif
<x-mail::button :url="url('/backoffice/reservationen/'.$r->id.'/edit')">
Im Backoffice bearbeiten
</x-mail::button>
</x-mail::message>
