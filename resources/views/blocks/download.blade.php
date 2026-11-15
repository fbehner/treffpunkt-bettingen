@php($file = \App\Support\Ui::file($data['file'] ?? null))
@if ($file)
    <section class="py-10" @if (filled($data['anchor'] ?? null)) id="{{ $data['anchor'] }}" @endif>
        <div class="container-site">
            <div class="flex flex-wrap items-center justify-between gap-6 rounded-2xl border-2 border-dunkel p-7 sm:p-8">
                <div class="max-w-3xl">
                    <h2 class="display text-[1.75rem] leading-tight">{{ $data['heading'] ?? '' }}</h2>
                    @if (filled($data['text'] ?? null))
                        <p class="mt-2 leading-relaxed text-dunkel-weich">{{ $data['text'] }}</p>
                    @endif
                </div>
                <a href="{{ $file }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-dunkel px-6 py-3 font-semibold text-white no-underline hover:bg-black hover:text-white" download>
                    <x-symbol name="download" class="size-5" />
                    {{ $data['button_label'] ?? 'Herunterladen (PDF)' }}
                </a>
            </div>
        </div>
    </section>
@endif
