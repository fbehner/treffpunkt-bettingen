<?php

namespace Tests\Feature;

use App\Mail\GuardianConfirmation;
use App\Mail\MembershipReceived;
use App\Mail\NewMembershipApplication;
use App\Models\MembershipApplication;
use App\Models\Setting;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
        $this->seed(ContentSeeder::class);
    }

    private function valid(array $overrides = []): array
    {
        return array_merge([
            'type' => 'einzel',
            'first_name' => 'Hans',
            'last_name' => 'Muster',
            'street' => 'Hauptstrasse 1',
            'zip_city' => '4126 Bettingen',
            'email' => 'hans@example.test',
            'statutes' => '1',
            'privacy' => '1',
        ], $overrides);
    }

    public function test_formular_zeigt_alle_mitgliedschaften_mit_beitraegen(): void
    {
        $this->get('/mitglied-werden')
            ->assertOk()
            ->assertSeeInOrder(['Einzelperson', 'CHF 50', 'Paar', 'CHF 80', 'Familie', 'CHF 80', 'Jugendliche/r', 'CHF 20', 'Kind', 'CHF 10', 'Verein / Institution', 'CHF 100']);
    }

    public function test_einzelmitgliedschaft_wird_gespeichert_und_gemeldet(): void
    {
        $this->post('/mitglied-werden', $this->valid())->assertRedirect('/mitglied-werden/danke');

        $a = MembershipApplication::sole();
        $this->assertSame('eingegangen', $a->status);
        $this->assertSame(50, $a->fee);
        $this->assertNotNull($a->statutes_accepted_at);

        Mail::assertSent(MembershipReceived::class, fn ($m) => $m->hasTo('hans@example.test'));
        Mail::assertSent(NewMembershipApplication::class, fn ($m) => $m->hasTo('info@treffpunkt-bettingen.ch'));
    }

    public function test_beitrag_aus_den_einstellungen_wird_festgehalten(): void
    {
        Setting::putMany(['fee_einzel' => 60]);

        $this->post('/mitglied-werden', $this->valid());

        $this->assertSame(60, MembershipApplication::sole()->fee);
    }

    public function test_statuten_und_datenschutz_muessen_bestaetigt_werden(): void
    {
        $this->post('/mitglied-werden', $this->valid(['statutes' => null, 'privacy' => null]))
            ->assertSessionHasErrors(['statutes', 'privacy']);

        $this->assertSame(0, MembershipApplication::count());
    }

    public function test_paar_braucht_zweite_person(): void
    {
        $this->post('/mitglied-werden', $this->valid(['type' => 'paar']))
            ->assertSessionHasErrors(['partner_first_name', 'partner_last_name']);
    }

    public function test_verein_braucht_namen_der_organisation(): void
    {
        $this->post('/mitglied-werden', $this->valid(['type' => 'verein']))
            ->assertSessionHasErrors('organisation');

        $this->post('/mitglied-werden', $this->valid(['type' => 'verein', 'organisation' => 'Turnverein Bettingen']))
            ->assertRedirect('/mitglied-werden/danke');

        $this->assertSame(100, MembershipApplication::sole()->fee);
    }

    public function test_alter_muss_zur_kindermitgliedschaft_passen(): void
    {
        $this->travelTo('2026-11-15');

        $this->post('/mitglied-werden', $this->valid([
            'type' => 'kind', 'birthdate' => '2018-01-01',
            'guardian_name' => 'Eva Muster', 'guardian_email' => 'eva@example.test',
        ]))->assertSessionHasErrors('birthdate');

        $this->post('/mitglied-werden', $this->valid([
            'type' => 'kind', 'birthdate' => '2013-01-01',
            'guardian_name' => 'Eva Muster', 'guardian_email' => 'eva@example.test',
        ]))->assertRedirect('/mitglied-werden/danke');
    }

    public function test_minderjaehrige_brauchen_bestaetigung_der_eltern(): void
    {
        $this->travelTo('2026-11-15');

        $this->post('/mitglied-werden', $this->valid([
            'type' => 'jugend', 'birthdate' => '2009-06-01',
            'guardian_name' => 'Eva Muster', 'guardian_email' => 'eva@example.test',
        ]))->assertRedirect('/mitglied-werden/danke');

        $a = MembershipApplication::sole();
        $this->assertSame('bestaetigung', $a->status);
        Mail::assertSent(GuardianConfirmation::class, fn ($m) => $m->hasTo('eva@example.test'));
        Mail::assertNotSent(NewMembershipApplication::class);

        // Ohne gültige Signatur geht nichts.
        $this->get('/mitglied-werden/bestaetigen/'.$a->id)->assertForbidden();

        $this->get(URL::temporarySignedRoute('membership.confirm', now()->addDay(), ['application' => $a->id]))->assertOk();

        $a->refresh();
        $this->assertSame('eingegangen', $a->status);
        $this->assertNotNull($a->guardian_confirmed_at);
        Mail::assertSent(NewMembershipApplication::class);
    }

    public function test_felder_anderer_mitgliedschaften_werden_nicht_gespeichert(): void
    {
        $this->post('/mitglied-werden', $this->valid([
            'organisation' => 'Soll nicht gespeichert werden',
            'partner_first_name' => 'Nein',
        ]));

        $a = MembershipApplication::sole();
        $this->assertNull($a->organisation);
        $this->assertNull($a->partner_first_name);
    }

    public function test_spam_falle(): void
    {
        $this->post('/mitglied-werden', $this->valid(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, MembershipApplication::count());
    }

    public function test_fehler_beim_mailversand_verliert_das_gesuch_nicht(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP nicht erreichbar'));

        $this->post('/mitglied-werden', $this->valid())->assertRedirect('/mitglied-werden/danke');

        $this->assertSame(1, MembershipApplication::count());
    }
}
