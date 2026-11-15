<x-layouts.site title="Danke">
    <section class="container-site pt-12 pb-24">
        <p class="kicker text-rost">Beitrittsgesuch</p>
        <h1 class="display mt-4 text-[clamp(2.75rem,7vw,5.75rem)] leading-[0.95]">Herzlichen <span class="text-rost">Dank!</span></h1>
        <p class="mt-6 max-w-3xl text-xl leading-relaxed">Wir haben euer Gesuch als <strong>{{ $type->label() }}</strong> erhalten und schicken euch eine Bestätigung per E-Mail.</p>
        @if ($type->needsGuardian())
            <p class="mt-4 max-w-3xl text-xl leading-relaxed">Die erziehungsberechtigte Person bekommt eine E-Mail mit einem Link, um das Gesuch zu bestätigen. Erst dann geht es an den Vorstand.</p>
        @endif
        <p class="mt-4 max-w-3xl text-lg leading-relaxed text-dunkel-weich">Keine E-Mail erhalten? Schaut im Spam-Ordner nach oder schreibt uns an <a href="mailto:{{ \App\Models\Setting::get('contact_email') }}">{{ \App\Models\Setting::get('contact_email') }}</a>.</p>
        <p class="mt-10"><a href="{{ route('home') }}" class="button">Zur Startseite</a></p>
    </section>
</x-layouts.site>
