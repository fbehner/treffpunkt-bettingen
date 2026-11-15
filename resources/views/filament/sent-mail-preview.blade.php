{{-- E-Mail in einem abgeschotteten Rahmen anzeigen (keine Skripte, keine Formulare). --}}
@php($html = $getRecord()->html)
@if (filled($html))
    <iframe
        sandbox=""
        srcdoc="{{ $html }}"
        title="Inhalt der E-Mail"
        style="width: 100%; height: 640px; border: 1px solid rgb(0 0 0 / 0.1); border-radius: 0.5rem; background: #fff"
    ></iframe>
@else
    <p class="text-sm text-gray-500">Kein Inhalt gespeichert.</p>
@endif
