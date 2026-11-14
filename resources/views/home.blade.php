@php
    // Kachelfarben: Hintergrund, Text, Icon – wie auf dem Plakat.
    $tileStyles = [
        'rost' => 'bg-rost text-white [&_.tile-icon]:text-white',
        'senf' => 'bg-senf text-dunkel [&_.tile-icon]:text-dunkel',
        'gruen' => 'bg-gruen text-white [&_.tile-icon]:text-white',
        'dunkel' => 'bg-dunkel text-white [&_.tile-icon]:text-senf',
        'beige' => 'bg-beige text-dunkel [&_.tile-icon]:text-dunkel',
    ];
@endphp
<x-layouts.site>
    <section class="container-site pt-8 sm:pt-14" aria-labelledby="titel">
        @if ($s['kicker'])
            <p class="kicker flex items-center gap-3">
                <x-symbol name="cup" class="size-6 text-rost" />
                {{ $s['kicker'] }}
            </p>
        @endif

        <h1 id="titel" class="display mt-6 text-[clamp(3.25rem,11vw,7.5rem)] leading-[0.95]">
            @if ($s['headline_dark'])
                <span class="block">{{ $s['headline_dark'] }}</span>
            @endif
            <span class="block whitespace-pre-line text-rost">{{ $s['headline_accent'] }}</span>
        </h1>

        @if ($s['intro'])
            <p class="mt-8 max-w-2xl text-xl leading-relaxed sm:text-2xl">{{ $s['intro'] }}</p>
        @endif
    </section>

    @if ($offers->isNotEmpty())
        <section class="container-site mt-10" aria-label="Angebote">
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($offers as $offer)
                    <li class="{{ $tileStyles[$offer->color] ?? $tileStyles['rost'] }} flex min-h-52 flex-col justify-between gap-8 sm:min-h-64 sm:gap-10 rounded-xl p-7">
                        <x-symbol :name="$offer->icon" class="tile-icon size-11" />
                        <div>
                            <h2 class="display text-[1.75rem] leading-tight">{{ $offer->title }}</h2>
                            <p class="mt-2 text-lg leading-snug">{{ $offer->description }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($s['closing_dark'] || $s['closing_accent'])
        <section class="container-site mt-16" aria-labelledby="ideen">
            <div class="border-t-[3px] border-dunkel pt-10">
                <h2 id="ideen" class="display text-[clamp(2.5rem,7vw,4.5rem)] leading-none">
                    {{ $s['closing_dark'] }} <span class="text-rost">{{ $s['closing_accent'] }}</span>
                </h2>
                @if ($s['closing_text'])
                    <p class="mt-6 max-w-2xl text-xl leading-relaxed">{{ $s['closing_text'] }}</p>
                @endif
                @if ($s['contact_email'])
                    <p class="mt-8">
                        <a class="button" href="mailto:{{ $s['contact_email'] }}">
                            <x-symbol name="mail" class="size-5" />
                            Schreibt uns
                        </a>
                    </p>
                @endif
            </div>
        </section>
    @endif
</x-layouts.site>
