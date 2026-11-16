<?php

namespace Tests\Feature;

use App\Filament\Backoffice\Resources\RoomReservations\Pages\CreateRoomReservation;
use App\Filament\Backoffice\Resources\RoomReservations\Pages\EditRoomReservation;
use App\Filament\Backoffice\Resources\RoomReservations\Pages\ListRoomReservations;
use App\Mail\NewReservationRequest;
use App\Mail\ReservationConfirmed;
use App\Mail\ReservationDeclined;
use App\Mail\ReservationReceived;
use App\Models\Membership;
use App\Models\RoomReservation;
use App\Models\SentMail;
use App\Models\Setting;
use App\Models\User;
use App\Support\RoomPackage;
use Database\Seeders\ContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(ContentSeeder::class);
        $this->travelTo('2027-03-01 10:00'); // Montag
    }

    /** Angemeldet mit der Rolle „Reservationen“ im Backoffice. */
    private function asReservationTeam(): void
    {
        $this->actingAs(User::factory()->create(['roles' => ['reservationen']]));
        Filament::setCurrentPanel(Filament::getPanel('backoffice'));
    }

    private function valid(array $overrides = []): array
    {
        return array_merge([
            'package' => 'gruppenraum',
            'date' => '2027-03-10',
            'start' => '19:00',
            'end' => '21:30',
            'purpose' => 'Vorstandssitzung Turnverein',
            'public_title' => 'Turnverein',
            'first_name' => 'Rita',
            'last_name' => 'Beispiel',
            'street' => 'Hauptstrasse 3',
            'zip_city' => '4126 Bettingen',
            'email' => 'rita@example.test',
            'phone' => '061 000 00 00',
            'house_rules' => '1',
            'privacy' => '1',
        ], $overrides);
    }

    private function reservation(array $overrides = []): RoomReservation
    {
        return RoomReservation::create(array_merge([
            'package' => 'gruppenraum',
            'starts_at' => '2027-03-10 19:00',
            'ends_at' => '2027-03-10 21:30',
            'status' => 'bestaetigt',
            'source' => 'intern',
            'purpose' => 'Jugendtreff',
            'first_name' => 'Rita',
            'last_name' => 'Beispiel',
            'email' => 'rita@example.test',
        ], $overrides));
    }

    public function test_pakete_kennen_ihre_ueberschneidungen(): void
    {
        $this->assertEqualsCanonicalizing(['kinderraum', 'untergeschoss', 'haus'], RoomPackage::Kinderraum->conflictingValues());
        $this->assertEqualsCanonicalizing(['cafe', 'haus'], RoomPackage::Cafe->conflictingValues());
        $this->assertCount(5, RoomPackage::Haus->conflictingValues());
    }

    public function test_formular_zeigt_pakete_und_im_menue_verlinkt(): void
    {
        $this->get('/reservieren')
            ->assertOk()
            ->assertSeeInOrder(['Café', 'Gruppen- und Jugendraum', 'Familien- und Kinderraum', 'Ganzes Untergeschoss', 'Ganzes Haus']);

        $this->get('/')->assertSee('Räume reservieren');
    }

    public function test_kalender_zeigt_nur_bestaetigte_ohne_personendaten(): void
    {
        $this->reservation(['public_title' => 'Jugendtreff am Mittwoch']);
        $this->reservation(['package' => 'cafe', 'status' => 'angefragt', 'public_title' => 'Geheime Anfrage', 'starts_at' => '2027-03-12 19:00', 'ends_at' => '2027-03-12 22:00']);
        $this->reservation(['package' => 'kinderraum', 'public_title' => null, 'starts_at' => '2027-03-13 14:00', 'ends_at' => '2027-03-13 17:00']);

        $this->get('/reservieren?monat=2027-03')
            ->assertOk()
            ->assertSee('Jugendtreff am Mittwoch')
            ->assertSee('Familien- und Kinderraum: Belegt')
            ->assertDontSee('Geheime Anfrage')
            ->assertDontSee('Rita');
    }

    public function test_anfrage_wird_gespeichert_berechnet_und_gemeldet(): void
    {
        Mail::fake();
        Setting::putMany([
            'tariff_gruppenraum_mitglied' => 20,
            'tariff_gruppenraum_gast' => 30,
            'reservation_email' => 'raeume@example.test',
        ]);

        $this->post('/reservieren', $this->valid())->assertRedirect('/reservieren/danke');

        $r = RoomReservation::sole();
        $this->assertSame('angefragt', $r->status);
        $this->assertSame('web', $r->source);
        $this->assertSame('2027-03-10 19:00', $r->starts_at->format('Y-m-d H:i'));
        $this->assertSame(75, $r->price); // 2.5 Std. × CHF 30
        $this->assertNotNull($r->house_rules_accepted_at);

        Mail::assertSent(ReservationReceived::class, fn ($m) => $m->hasTo('rita@example.test'));
        Mail::assertSent(NewReservationRequest::class, fn ($m) => $m->hasTo('raeume@example.test'));

        $this->get('/reservieren/danke')->assertOk()->assertSee('Gruppen- und Jugendraum');
    }

    public function test_mitglieder_bekommen_den_mitgliedertarif(): void
    {
        Mail::fake();
        Setting::putMany(['tariff_gruppenraum_mitglied' => 20, 'tariff_gruppenraum_gast' => 30]);

        $this->post('/reservieren', $this->valid(['is_member' => '1', 'member_number' => '12']));

        $r = RoomReservation::sole();
        $this->assertTrue($r->is_member);
        $this->assertSame('12', $r->member_number);
        $this->assertSame(50, $r->price);
    }

    public function test_meldung_geht_ohne_eigene_adresse_an_die_kontakt_email(): void
    {
        Mail::fake();

        $this->post('/reservieren', $this->valid());

        Mail::assertSent(NewReservationRequest::class, fn ($m) => $m->hasTo('info@treffpunkt-bettingen.ch'));
    }

    public function test_belegter_raum_kann_nicht_angefragt_werden(): void
    {
        Mail::fake();
        $this->reservation(['package' => 'haus']);

        $this->post('/reservieren', $this->valid(['package' => 'kinderraum', 'start' => '20:00', 'end' => '22:00']))
            ->assertSessionHasErrors('date');

        $this->assertSame(1, RoomReservation::count());
    }

    public function test_andere_raeume_und_offene_anfragen_blockieren_nicht(): void
    {
        Mail::fake();
        $this->reservation(['package' => 'kinderraum']);
        $this->reservation(['package' => 'gruppenraum', 'status' => 'angefragt']);
        $this->reservation(['package' => 'gruppenraum', 'status' => 'abgelehnt']);

        $this->post('/reservieren', $this->valid(['package' => 'gruppenraum']))->assertSessionHasNoErrors();
        $this->post('/reservieren', $this->valid(['package' => 'cafe']))->assertSessionHasNoErrors();
    }

    public function test_anschliessende_zeiten_ueberschneiden_sich_nicht(): void
    {
        Mail::fake();
        $this->reservation(); // 19.00–21.30

        $this->post('/reservieren', $this->valid(['start' => '21:30', 'end' => '23:00']))->assertSessionHasNoErrors();
        $this->post('/reservieren', $this->valid(['start' => '17:00', 'end' => '19:00']))->assertSessionHasNoErrors();
    }

    public function test_cafe_erst_nach_betriebsschluss(): void
    {
        Mail::fake();

        $this->post('/reservieren', $this->valid(['package' => 'cafe', 'start' => '14:00', 'end' => '16:00']))
            ->assertSessionHasErrors('date');
        $this->post('/reservieren', $this->valid(['package' => 'haus', 'start' => '17:30', 'end' => '20:00']))
            ->assertSessionHasErrors('date');
        $this->post('/reservieren', $this->valid(['package' => 'untergeschoss', 'start' => '14:00', 'end' => '16:00']))
            ->assertSessionHasNoErrors();
        $this->post('/reservieren', $this->valid(['package' => 'cafe', 'start' => '18:00', 'end' => '20:00']))
            ->assertSessionHasNoErrors();
    }

    public function test_zeitregeln(): void
    {
        Mail::fake();

        // Vorlauf von 3 Tagen
        $this->post('/reservieren', $this->valid(['date' => '2027-03-02']))->assertSessionHasErrors('date');
        // Ende vor Beginn
        $this->post('/reservieren', $this->valid(['start' => '20:00', 'end' => '19:00']))->assertSessionHasErrors('date');
        // Nicht im Halbstundentakt bzw. ausserhalb der Zeiten
        $this->post('/reservieren', $this->valid(['start' => '19:15']))->assertSessionHasErrors('start');
        $this->post('/reservieren', $this->valid(['start' => '05:00']))->assertSessionHasErrors('start');
        // Mehr als 12 Monate im Voraus
        $this->post('/reservieren', $this->valid(['date' => '2028-04-01']))->assertSessionHasErrors('date');

        $this->assertSame(0, RoomReservation::count());
    }

    public function test_pflichtangaben_und_spamfalle(): void
    {
        $this->post('/reservieren', $this->valid(['purpose' => '', 'house_rules' => null, 'privacy' => null, 'phone' => '']))
            ->assertSessionHasErrors(['purpose', 'house_rules', 'privacy', 'phone']);
        $this->post('/reservieren', $this->valid(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, RoomReservation::count());
    }

    public function test_backoffice_rollen_sind_getrennt(): void
    {
        $reservationen = User::factory()->create(['roles' => ['reservationen']]);
        $mitglieder = User::factory()->create(['roles' => ['mitglieder']]);
        $webadmin = User::factory()->create(['roles' => ['admin']]);

        $this->actingAs($reservationen)->get('/backoffice')->assertOk()->assertDontSee('Aktive Mitgliedschaften');
        $this->actingAs($reservationen)->get('/backoffice/reservationen')->assertOk();
        $this->actingAs($reservationen)->get('/backoffice/reservationen/create')->assertOk();
        $this->actingAs($reservationen)->get('/backoffice/memberships')->assertForbidden();
        $this->actingAs($reservationen)->get('/backoffice/membership-applications')->assertForbidden();
        $this->actingAs($reservationen)->get('/admin')->assertForbidden();

        $this->actingAs($mitglieder)->get('/backoffice/reservationen')->assertForbidden();
        $this->actingAs($webadmin)->get('/backoffice')->assertForbidden();
    }

    public function test_bestaetigen_sendet_mail(): void
    {
        Mail::fake();
        $this->asReservationTeam();
        $r = $this->reservation(['status' => 'angefragt', 'source' => 'web']);

        Livewire::test(EditRoomReservation::class, ['record' => $r->getRouteKey()])
            ->callAction('confirm', ['send_mail' => true, 'decision_note' => 'Schlüssel im Dorfladen abholen.'])
            ->assertHasNoActionErrors();

        $r->refresh();
        $this->assertSame('bestaetigt', $r->status);
        $this->assertNotNull($r->decided_at);
        Mail::assertSent(ReservationConfirmed::class, fn ($m) => $m->hasTo('rita@example.test'));
    }

    public function test_bestaetigen_nicht_moeglich_wenn_belegt(): void
    {
        Mail::fake();
        $this->asReservationTeam();
        $this->reservation(['package' => 'untergeschoss']);
        $r = $this->reservation(['status' => 'angefragt', 'source' => 'web']);

        Livewire::test(EditRoomReservation::class, ['record' => $r->getRouteKey()])
            ->assertActionDisabled('confirm');

        $this->assertSame('angefragt', $r->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_ablehnen_aus_der_liste(): void
    {
        Mail::fake();
        $this->asReservationTeam();
        $r = $this->reservation(['status' => 'angefragt', 'source' => 'web']);

        Livewire::test(ListRoomReservations::class)
            ->assertCanSeeTableRecords([$r])
            ->callTableAction('decline', $r, ['send_mail' => true, 'decision_note' => 'Leider schon vergeben.'])
            ->assertHasNoTableActionErrors();

        $r->refresh();
        $this->assertSame('abgelehnt', $r->status);
        $this->assertSame('Leider schon vergeben.', $r->decision_note);
        Mail::assertSent(ReservationDeclined::class, fn ($m) => $m->hasTo('rita@example.test'));
    }

    public function test_eigener_eintrag_ohne_ueberschneidung(): void
    {
        $this->asReservationTeam();
        $this->reservation(['package' => 'cafe', 'starts_at' => '2027-03-13 19:00', 'ends_at' => '2027-03-13 22:30']);

        // Ganzes Haus am selben Abend: kollidiert mit dem Café
        Livewire::test(CreateRoomReservation::class)
            ->fillForm([
                'package' => 'haus', 'date' => '13.03.2027', 'start_time' => '18:00', 'end_time' => '20:00',
                'purpose' => 'Dorffest', 'organisation' => 'Verein', 'status' => 'bestaetigt', 'source' => 'intern',
            ])
            ->call('create')
            ->assertHasFormErrors(['end_time']);

        // Untergeschoss ist frei – auch tagsüber und ohne Vorlauf
        Livewire::test(CreateRoomReservation::class)
            ->fillForm([
                'package' => 'untergeschoss', 'date' => '13.03.2027', 'start_time' => '19:00', 'end_time' => '22:30',
                'purpose' => 'Jugendtreff', 'organisation' => 'Verein Treffpunkt', 'status' => 'bestaetigt', 'source' => 'intern',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $r = RoomReservation::where('package', 'untergeschoss')->sole();
        $this->assertSame('2027-03-13 22:30', $r->ends_at->format('Y-m-d H:i'));
        $this->assertNotNull($r->decided_at);
    }

    public function test_bearbeiten_behaelt_datum_und_zeiten(): void
    {
        $this->asReservationTeam();
        $r = $this->reservation(['price' => 40]);

        Livewire::test(EditRoomReservation::class, ['record' => $r->getRouteKey()])
            ->assertSchemaStateSet(['date' => '10.03.2027', 'start_time' => '19:00', 'end_time' => '21:30'])
            ->fillForm(['end_time' => '22:00'])
            ->call('save')
            ->assertHasNoFormErrors();

        $r->refresh();
        $this->assertSame('22:00', $r->ends_at->format('H:i'));
        $this->assertSame(40, $r->price);
    }

    public function test_mitgliedschaft_wird_zur_email_gefunden(): void
    {
        $m = Membership::create(['number' => 7, 'type' => 'einzel', 'fee' => 50, 'email' => 'Rita@Example.test', 'joined_at' => '2026-12-01']);
        $r = $this->reservation(['status' => 'angefragt']);

        $this->assertTrue($r->matchingMembership()->is($m));

        $m->update(['left_at' => '2027-01-31']);
        $this->assertNull($r->matchingMembership());
    }

    public function test_tarife_in_den_einstellungen(): void
    {
        $this->actingAs(User::factory()->create(['roles' => ['admin']]));

        $this->get('/admin/einstellungen')->assertOk()->assertSee('Raumtarife')->assertSee('Ganzes Haus – Mitglieder');

        Setting::putMany(['tariff_haus_gast' => 80]);
        $this->assertSame(80, RoomPackage::Haus->hourlyRate(false));
        $this->assertSame(120, RoomPackage::Haus->price(90, false));
        $this->get('/reservieren')->assertSee('andere CHF 80');
    }

    public function test_mails_landen_im_protokoll_der_reservationen(): void
    {
        $this->post('/reservieren', $this->valid());

        $this->assertSame(2, SentMail::where('category', 'reservationen')->count());

        $reservationen = User::factory()->create(['roles' => ['reservationen']]);
        $mitglieder = User::factory()->create(['roles' => ['mitglieder']]);
        $webadmin = User::factory()->create(['roles' => ['admin']]);

        $this->actingAs($reservationen)->get('/backoffice/sent-mails')->assertOk()->assertSee('Eure Reservationsanfrage');
        $this->actingAs($mitglieder)->get('/backoffice/sent-mails')->assertOk()->assertDontSee('Eure Reservationsanfrage');
        $this->actingAs($webadmin)->get('/admin/sent-mails')->assertOk()->assertDontSee('Eure Reservationsanfrage');
    }
}
