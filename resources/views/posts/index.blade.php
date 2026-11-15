<x-layouts.site title="Aktuelles" description="Termine und Neuigkeiten rund um den Treffpunkt Bettingen.">
    <header class="container-site pt-8 pb-6 sm:pt-12">
        <p class="kicker text-rost">Aktuelles</p>
        <h1 class="display mt-4 text-[clamp(2.75rem,7vw,5.75rem)] leading-[0.95]">Was läuft. <span class="text-rost">Was kommt.</span></h1>
    </header>

    @if ($upcoming->isNotEmpty())
        <section class="container-site py-8" aria-labelledby="termine">
            <h2 id="termine" class="display text-[clamp(2rem,4vw,3rem)] leading-none">Termine</h2>
            <ul class="mt-6 flex flex-col gap-4" role="list">
                @foreach ($upcoming as $event)
                    <li class="grid gap-x-8 gap-y-3 rounded-2xl bg-white p-6 sm:grid-cols-[9rem_1fr] sm:p-7">
                        <div class="flex items-baseline gap-3 sm:flex-col sm:gap-0">
                            <p class="display text-5xl leading-none text-rost">{{ $event->event_starts_at->format('j.') }}</p>
                            <p class="font-semibold">{{ $event->event_starts_at->translatedFormat('F Y') }}</p>
                        </div>
                        <div>
                            <h3 class="display text-2xl leading-tight">
                                <a href="{{ route('posts.show', $event->slug) }}" class="text-dunkel no-underline hover:text-rost">{{ $event->title }}</a>
                            </h3>
                            <p class="mt-1.5 text-dunkel-weich">
                                {{ $event->event_starts_at->translatedFormat('l') }}
                                @if ($event->event_starts_at->format('H:i') !== '00:00')
                                    · {{ $event->event_starts_at->format('H.i') }} Uhr
                                @endif
                                @if ($event->event_location)
                                    · {{ $event->event_location }}
                                @endif
                            </p>
                            @if ($event->excerpt)
                                <p class="mt-2 leading-relaxed">{{ $event->excerpt }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="container-site py-8 pb-20" aria-labelledby="neuigkeiten">
        <h2 id="neuigkeiten" class="display text-[clamp(2rem,4vw,3rem)] leading-none">Neuigkeiten</h2>
        @if ($posts->isEmpty())
            <p class="mt-6 text-lg">Noch keine Beiträge.</p>
        @else
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    @include('posts._card', ['post' => $post])
                @endforeach
            </div>
            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </section>
</x-layouts.site>
