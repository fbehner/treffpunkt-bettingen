<x-layouts.site title="Einladung ungültig">
    <section class="container-site pt-12 pb-24">
        <p class="kicker text-rost">Einladung</p>
        @if ($invitation?->accepted_at)
            <h1 class="display mt-4 text-[clamp(2.5rem,6vw,4.75rem)] leading-[0.95]">Diese Einladung wurde <span class="text-rost">schon genutzt.</span></h1>
            <p class="mt-6 max-w-2xl text-xl leading-relaxed">Das Konto ist bereits angelegt. Falls du dein Passwort vergessen hast, nutze auf der Anmeldeseite „Passwort vergessen?“.</p>
            <p class="mt-10"><a href="{{ url('/admin/login') }}" class="button">Zur Anmeldung</a></p>
        @else
            <h1 class="display mt-4 text-[clamp(2.5rem,6vw,4.75rem)] leading-[0.95]">Dieser Link ist <span class="text-rost">nicht mehr gültig.</span></h1>
            <p class="mt-6 max-w-2xl text-xl leading-relaxed">Einladungslinks gelten {{ \App\Models\Invitation::VALID_DAYS }} Tage. Bitte die Person, die dich eingeladen hat, um eine neue Einladung.</p>
        @endif
    </section>
</x-layouts.site>
