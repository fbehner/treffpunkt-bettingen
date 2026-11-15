{{-- Kleine Überschrift + Überschrift eines Bausteins. --}}
@if (filled($data['kicker'] ?? null))
    <p class="kicker {{ $kickerClass ?? 'text-rost' }}">{{ $data['kicker'] }}</p>
@endif
@if (filled($data['heading'] ?? null))
    <h2 class="display mt-3 text-[clamp(2.25rem,4.6vw,3.75rem)] leading-none">{{ $data['heading'] }}</h2>
@endif
