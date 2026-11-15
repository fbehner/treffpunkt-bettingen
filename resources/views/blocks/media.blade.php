@php
    $img = \App\Support\Ui::file($data['image'] ?? null);
    $right = ($data['image_side'] ?? 'left') === 'right';
@endphp
<section class="{{ \App\Support\Ui::section($data['background'] ?? null) }} py-10 sm:py-12" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div class="container-site grid items-start gap-8 lg:grid-cols-[1.15fr_1fr] lg:gap-12">
        @if ($img)
            <img src="{{ $img }}" alt="{{ $data['image_alt'] ?? '' }}" loading="lazy" class="aspect-[16/10] w-full rounded-2xl object-cover {{ $right ? 'lg:order-2' : '' }}">
        @endif
        <div class="{{ $right ? 'lg:order-1' : '' }}">
            @if (filled($data['kicker'] ?? null))
                <p class="kicker text-rost">{{ $data['kicker'] }}</p>
            @endif
            <h2 class="display mt-2 text-[clamp(2rem,3.6vw,2.6rem)] leading-tight">{{ $data['heading'] ?? '' }}</h2>
            @if (filled($data['body'] ?? null))
                <div class="prose-site mt-4 text-[1.1rem]">{!! str($data['body'])->sanitizeHtml() !!}</div>
            @endif
        </div>
    </div>
</section>
