@php($t = \App\Support\Ui::tone($data['tone'] ?? 'rost'))
<section class="py-10" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
    <div class="container-site">
        <div class="{{ $t['bg'] }} {{ $t['text'] }} flex flex-wrap items-center justify-between gap-6 rounded-2xl p-8 sm:p-10">
            <div class="max-w-2xl">
                <h2 class="display text-[clamp(1.9rem,3.5vw,2.6rem)] leading-tight">{{ $data['heading'] ?? '' }}</h2>
                @if (filled($data['text'] ?? null))
                    <p class="mt-3 text-lg leading-relaxed">{{ $data['text'] }}</p>
                @endif
            </div>
            @if (filled($data['button_label'] ?? null))
                <a href="{{ $data['button_url'] ?? '#' }}" class="inline-flex min-h-11 items-center rounded-lg bg-white px-6 py-3 font-bold text-dunkel no-underline hover:bg-creme hover:text-dunkel">{{ $data['button_label'] }}</a>
            @endif
        </div>
    </div>
</section>
