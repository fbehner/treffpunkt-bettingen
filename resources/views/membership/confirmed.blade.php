<x-layouts.site title="Bestätigt">
    <section class="container-site pt-12 pb-24">
        <p class="kicker text-rost">Beitrittsgesuch</p>
        <h1 class="display mt-4 text-[clamp(2.75rem,7vw,5.75rem)] leading-[0.95]">Vielen Dank, <span class="text-rost">bestätigt.</span></h1>
        <p class="mt-6 max-w-3xl text-xl leading-relaxed">Das Gesuch von {{ $application->first_name }} {{ $application->last_name }} ist bestätigt und geht jetzt an den Vorstand.</p>
        <p class="mt-10"><a href="{{ route('home') }}" class="button">Zur Startseite</a></p>
    </section>
</x-layouts.site>
