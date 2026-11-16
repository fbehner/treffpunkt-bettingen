<x-layouts.site title="Danke">
    <section class="container-site pt-12 pb-24">
        <p class="kicker text-rost">Raumreservation</p>
        <h1 class="display mt-4 text-[clamp(2.75rem,7vw,5.75rem)] leading-[0.95]">Anfrage <span class="text-rost">erhalten!</span></h1>
        <p class="mt-6 max-w-3xl text-xl leading-relaxed">
            Wir haben eure Anfrage für <strong>{{ $reservation->packageInfo()->label() }}</strong> am <strong>{{ $reservation->timeLabel() }}</strong> erhalten und schicken euch eine Bestätigung des Eingangs per E-Mail.
        </p>
        <p class="mt-4 max-w-3xl text-xl leading-relaxed">Verbindlich ist die Reservation erst, wenn wir sie bestätigt haben. Wir melden uns so bald wie möglich.</p>
        <p class="mt-4 max-w-3xl text-lg leading-relaxed text-dunkel-weich">Keine E-Mail erhalten? Schaut im Spam-Ordner nach oder schreibt uns an <a href="mailto:{{ \App\Models\Setting::reservationEmail() }}">{{ \App\Models\Setting::reservationEmail() }}</a>.</p>
        <p class="mt-10 flex flex-wrap gap-3">
            <a href="{{ route('reservation.create') }}" class="button">Zum Belegungskalender</a>
            <a href="{{ route('home') }}" class="button-outline">Zur Startseite</a>
        </p>
    </section>
</x-layouts.site>
