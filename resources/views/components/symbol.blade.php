@props(['name', 'class' => 'size-11'])
{{-- Linien-Icons im Stil des Plakats. Neue Icons: Pfad hier ergänzen und in App\Models\Offer::ICONS eintragen. --}}
@php
    $paths = [
        'book' => '<path d="M12 6c-2-1.5-5-2-9-2v14c4 0 7 .5 9 2 2-1.5 5-2 9-2V4c-4 0-7 .5-9 2z"/><path d="M12 6v14"/>',
        'mic' => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0"/><path d="M12 18v3M9 21h6"/>',
        'puppet' => '<path d="M9 7l3-4 3 4"/><circle cx="12" cy="10" r="3"/><path d="M6 21c0-4 2.7-7 6-7s6 3 6 7"/><path d="M3 13l4 2M21 13l-4 2"/>',
        'flask' => '<path d="M9 3h6M10 3v6l-5 10a1.5 1.5 0 0 0 1.3 2h11.4a1.5 1.5 0 0 0 1.3-2L14 9V3"/><path d="M7 15h10"/>',
        'chat' => '<path d="M3 4h12v8H8l-4 3v-3H3z"/><path d="M15 8h6v8h-1v3l-4-3h-5v-4"/>',
        'film' => '<rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M8 3v18M16 3v18M4 8h4M4 12h4M4 16h4M16 8h4M16 12h4M16 16h4"/>',
        'egg' => '<path d="M12 3c-4 0-7 6-7 10.5a7 7 0 0 0 14 0C19 9 16 3 12 3z"/><path d="M5.5 12l3-2 3.5 2 3.5-2 3 2M5.6 16l3-2 3.4 2 3.4-2 3 2"/>',
        'music' => '<path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>',
        'dance' => '<circle cx="13" cy="4" r="2"/><path d="M13 6l-1 6 3 3-1 6M12 12l-4 2M13 8l4 2M12 15l-3 6"/>',
        'cup' => '<path d="M4 9h12v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V9z"/><path d="M16 11h1.5a2.5 2.5 0 0 1 0 5H16"/><path d="M8 3v3M12 3v3"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M17 14c2.5 0 4 2 4 5"/>',
        'heart' => '<path d="M12 20s-8-4.8-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 9c0 6.2-8 11-8 11z"/>',
        'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['star'] !!}</svg>
