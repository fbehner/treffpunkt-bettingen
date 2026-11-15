@php($img = \App\Support\Ui::file($data['image'] ?? null))
<section class="container-site grid items-center gap-10 pt-6 pb-16 sm:pt-10 lg:grid-cols-[1.1fr_1fr] lg:gap-12" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div>
        @if (filled($data['kicker'] ?? null))
            <p class="kicker text-rost">{{ $data['kicker'] }}</p>
        @endif
        <h1 class="display mt-5 text-[clamp(3.25rem,7.5vw,6.5rem)] leading-[0.95]">
            {{ $data['headline'] ?? '' }}
            @if (filled($data['headline_accent'] ?? null))
                <span class="text-rost">{{ $data['headline_accent'] }}</span>
            @endif
        </h1>
        @if (filled($data['lead'] ?? null))
            <p class="mt-7 max-w-xl text-xl leading-relaxed sm:text-[1.35rem]">{{ $data['lead'] }}</p>
        @endif
        @if (filled($data['primary_label'] ?? null) || filled($data['secondary_label'] ?? null))
            <div class="mt-8 flex flex-wrap gap-3">
                @if (filled($data['primary_label'] ?? null))
                    <a href="{{ $data['primary_url'] ?? '#' }}" class="button text-lg">{{ $data['primary_label'] }}</a>
                @endif
                @if (filled($data['secondary_label'] ?? null))
                    <a href="{{ $data['secondary_url'] ?? '#' }}" class="button-outline text-lg">{{ $data['secondary_label'] }}</a>
                @endif
            </div>
        @endif
    </div>
    @if ($img)
        <figure class="relative">
            <img src="{{ $img }}" alt="{{ $data['image_alt'] ?? '' }}" class="aspect-[4/3.4] w-full rounded-2xl object-cover" fetchpriority="high">
            @if (filled($data['image_caption'] ?? null))
                <figcaption class="absolute bottom-3.5 left-3.5 rounded-md bg-dunkel/85 px-2.5 py-1.5 text-[13px] text-creme">{{ $data['image_caption'] }}</figcaption>
            @endif
        </figure>
    @endif
</section>
