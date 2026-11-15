@php
    $offers = \App\Models\Offer::query()->visible()->get();
    $tileStyles = [
        'rost' => 'bg-rost text-white [&_.tile-icon]:text-white',
        'senf' => 'bg-senf text-dunkel [&_.tile-icon]:text-dunkel',
        'gruen' => 'bg-gruen text-white [&_.tile-icon]:text-white',
        'dunkel' => 'bg-dunkel text-white [&_.tile-icon]:text-senf',
        'beige' => 'bg-beige text-dunkel [&_.tile-icon]:text-dunkel',
    ];
@endphp
<section class="border-t-[3px] border-dunkel py-16 sm:py-20" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div class="container-site">
        @include('blocks._heading')
        @if (filled($data['intro'] ?? null))
            <p class="mt-5 max-w-3xl text-xl leading-relaxed">{{ $data['intro'] }}</p>
        @endif

        @if ($offers->isNotEmpty())
            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($offers as $offer)
                    <li class="{{ $tileStyles[$offer->color] ?? $tileStyles['rost'] }} flex min-h-48 flex-col justify-between gap-6 rounded-xl p-6">
                        <x-symbol :name="$offer->icon" class="tile-icon size-10" />
                        <div>
                            <h3 class="display text-[1.6rem] leading-tight">{{ $offer->title }}</h3>
                            <p class="mt-1.5 text-[1.05rem] leading-snug">{{ $offer->description }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if (filled($data['closing_dark'] ?? null) || filled($data['closing_accent'] ?? null) || filled($data['primary_label'] ?? null))
            <div class="mt-12 flex flex-wrap items-end justify-between gap-6">
                <p class="display text-[clamp(2.5rem,6vw,4.25rem)] leading-none">{{ $data['closing_dark'] ?? '' }} <span class="text-rost">{{ $data['closing_accent'] ?? '' }}</span></p>
                <div class="flex flex-wrap gap-3">
                    @if (filled($data['primary_label'] ?? null))
                        <a href="{{ $data['primary_url'] ?? '#' }}" class="button">{{ $data['primary_label'] }}</a>
                    @endif
                    @if (filled($data['secondary_label'] ?? null))
                        <a href="{{ $data['secondary_url'] ?? '#' }}" class="button-outline">{{ $data['secondary_label'] }}</a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>
