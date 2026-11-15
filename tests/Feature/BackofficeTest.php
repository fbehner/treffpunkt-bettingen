<?php

namespace Tests\Feature;

use App\Filament\Backoffice\Resources\MembershipApplications\Pages\EditMembershipApplication;
use App\Filament\Backoffice\Resources\MembershipApplications\Pages\ListMembershipApplications;
use App\Filament\Backoffice\Resources\MembershipFees\Pages\ListMembershipFees;
use App\Filament\Backoffice\Resources\Memberships\Pages\EditMembership;
use App\Filament\Backoffice\Resources\Memberships\Pages\ListMemberships;
use App\Mail\MembershipWelcome;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\MembershipFee;
use App\Models\SentMail;
use App\Models\User;
use App\Support\Csv;
use App\Support\MembershipMails;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class BackofficeTest extends TestCase
{
    use RefreshDatabase;

    private function application(array $overrides = []): MembershipApplication
    {
        return MembershipApplication::create(array_merge([
            'type' => 'einzel',
            'source' => 'papier',
            'received_at' => '2026-11-14',
            'fee' => 50,
            'first_name' => 'Hans',
            'last_name' => 'Muster',
            'street' => 'Hauptstrasse 1',
            'zip_city' => '4126 Bettingen',
            'email' => 'hans@example.test',
            'status' => 'eingegangen',
        ], $overrides));
    }

    public function test_backoffice_nur_mit_rolle_mitgliederverwaltung(): void
    {
        $webadmin = User::factory()->create(['roles' => ['admin']]);
        $redaktion = User::factory()->create(['roles' => ['redaktion']]);
        $mitglieder = User::factory()->create(['roles' => ['mitglieder']]);
        $inaktiv = User::factory()->create(['roles' => ['mitglieder'], 'is_active' => false]);

        $this->actingAs($webadmin)->get('/backoffice')->assertForbidden();
        $this->actingAs($redaktion)->get('/backoffice')->assertForbidden();
        $this->actingAs($inaktiv)->get('/backoffice')->assertForbidden();

        foreach (['/backoffice', '/backoffice/memberships', '/backoffice/membership-applications', '/backoffice/membership-fees', '/backoffice/membership-applications/create', '/backoffice/memberships/create'] as $url) {
            $this->actingAs($mitglieder)->get($url)->assertOk();
        }

        // Mitgliederverwaltung allein kommt nicht in die Website-Verwaltung.
        $this->actingAs($mitglieder)->get('/admin')->assertForbidden();
    }

    public function test_ohne_anmeldung_zum_login(): void
    {
        $this->get('/backoffice/memberships')->assertRedirect('/backoffice/login');
    }

    public function test_beide_rollen_kommen_in_beide_bereiche(): void
    {
        $user = User::factory()->create(['roles' => ['admin', 'mitglieder']]);

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get('/backoffice')->assertOk();
    }

    public function test_aufnahme_einer_familie_legt_mitgliedschaft_mit_personen_an(): void
    {
        $application = $this->application([
            'type' => 'familie', 'fee' => 80,
            'partner_first_name' => 'Urs', 'partner_last_name' => 'Muster',
            'children' => "Lena 2015\nTim, Jg. 2019",
        ]);

        $membership = $application->accept(Carbon::parse('2026-12-10'));

        $this->assertSame(1, $membership->number);
        $this->assertSame('familie', $membership->type);
        $this->assertSame(80, $membership->fee);
        $this->assertSame('2026-12-10', $membership->joined_at->toDateString());
        $this->assertSame(['haupt', 'partner', 'kind', 'kind'], $membership->people->pluck('relation')->all());
        $this->assertSame([null, null, 2015, 2019], $membership->people->pluck('birth_year')->all());
        $this->assertSame('aufgenommen', $application->fresh()->status);
        $this->assertSame($membership->id, $application->fresh()->membership_id);

        $this->assertSame(2, $this->application()->accept(now())->number);
    }

    public function test_minderjaehrige_online_erst_nach_bestaetigung_aufnehmbar(): void
    {
        $web = $this->application(['type' => 'jugend', 'source' => 'web', 'status' => 'bestaetigung', 'birthdate' => '2009-06-01']);
        $this->assertFalse($web->canBeAccepted());

        $web->update(['guardian_confirmed_at' => now(), 'status' => 'eingegangen']);
        $this->assertTrue($web->fresh()->canBeAccepted());

        // Auf Papier unterschreiben die Eltern direkt.
        $papier = $this->application(['type' => 'jugend', 'source' => 'papier', 'birthdate' => '2009-06-01']);
        $this->assertTrue($papier->canBeAccepted());
    }

    public function test_jahresbeitraege_werden_einmal_und_nur_fuer_mitglieder_im_jahr_erzeugt(): void
    {
        $a = $this->application(['donation' => 30])->accept(Carbon::parse('2026-12-01'));
        $b = $this->application(['email' => 'b@example.test'])->accept(Carbon::parse('2027-03-01'));
        $c = $this->application(['email' => 'c@example.test'])->accept(Carbon::parse('2026-12-01'));
        $c->update(['left_at' => '2026-12-31', 'exit_reason' => 'austritt']);

        $this->assertSame(2, MembershipFee::generateFor(2027)); // a und b
        $this->assertSame(0, MembershipFee::generateFor(2027));

        $fee = $a->fees()->where('year', 2027)->first();
        $this->assertSame(50, $fee->amount);
        $this->assertSame(30, $fee->donation, 'Gönnerbeitrag aus dem Gesuch beim ersten Beitrag');
        $this->assertTrue($b->fees()->where('year', 2027)->exists());
        $this->assertFalse($c->fees()->where('year', 2027)->exists());

        // Gönnerbeitrag nur einmal.
        MembershipFee::generateFor(2028);
        $this->assertNull($a->fees()->where('year', 2028)->first()->donation);
    }

    public function test_stimmrecht_und_altersuebergang(): void
    {
        $this->travelTo('2026-11-15');

        $familie = $this->application([
            'type' => 'familie', 'partner_first_name' => 'Urs', 'children' => "Lena 2008\nTim 2015",
        ])->accept(now());
        $this->assertSame(3, $familie->votingCount(), 'Eltern + Lena (18)');

        $jugend = $this->application(['type' => 'jugend', 'birthdate' => '2008-01-01'])->accept(now());
        $this->assertSame('Einzelperson (18 Jahre erreicht)', $jugend->transitionHint());

        $kind = $this->application(['type' => 'kind', 'birthdate' => '2013-01-01'])->accept(now());
        $this->assertNull($kind->transitionHint());
        $this->assertSame(0, $kind->votingCount());

        $verein = $this->application(['type' => 'verein', 'organisation' => 'TV Bettingen'])->accept(now());
        $this->assertSame(1, $verein->votingCount());
        $this->assertSame('kontakt', $verein->people->first()->relation);
    }

    public function test_aktive_mitglieder(): void
    {
        $this->travelTo('2027-02-01');
        $a = $this->application()->accept(Carbon::parse('2026-12-01'));
        $b = $this->application(['email' => 'b@example.test'])->accept(Carbon::parse('2026-12-01'));
        $b->update(['left_at' => '2026-12-31', 'exit_reason' => 'austritt']);
        $c = $this->application(['email' => 'c@example.test'])->accept(Carbon::parse('2026-12-01'));
        $c->update(['left_at' => '2027-12-31', 'exit_reason' => 'austritt']);

        $this->assertEqualsCanonicalizing([$a->id, $c->id], Membership::active()->pluck('id')->all());
    }

    public function test_csv_export_schuetzt_vor_formeln(): void
    {
        $this->assertSame("'=HYPERLINK(1)", Csv::safe('=HYPERLINK(1)'));
        $this->assertSame('Muster', Csv::safe('Muster'));
    }

    public function test_offener_goennerbeitrag(): void
    {
        $m = $this->application(['donation' => 40])->accept(Carbon::parse('2026-12-01'));
        $ohne = $this->application(['email' => 'b@example.test'])->accept(Carbon::parse('2026-12-01'));

        // Zugesagt im Gesuch, noch kein Jahresbeitrag erzeugt.
        $this->assertSame(40, $m->openDonation());
        $this->assertTrue($m->donationOnlyPledged());
        $this->assertSame(0, $ohne->openDonation());

        // Im offenen Jahresbeitrag enthalten.
        MembershipFee::generateFor(2027);
        $m->refresh();
        $this->assertSame(40, $m->openDonation());
        $this->assertFalse($m->donationOnlyPledged());

        // Bezahlt → nichts mehr offen.
        $m->fees()->update(['paid_at' => '2027-01-20']);
        $this->assertSame(0, $m->refresh()->openDonation());

        // Filter in der Liste.
        Filament::setCurrentPanel(Filament::getPanel('backoffice'));
        $this->actingAs(User::factory()->create(['roles' => ['mitglieder']]));
        $offen = $this->application(['email' => 'c@example.test', 'donation' => 25])->accept(Carbon::parse('2026-12-01'));
        Livewire::test(ListMemberships::class)
            ->assertSee('Gönner offen')
            ->filterTable('open_donation')
            ->assertCanSeeTableRecords([$offen])
            ->assertCanNotSeeTableRecords([$m, $ohne]);
    }

    public function test_alle_filter_in_den_listen_funktionieren(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('backoffice'));
        $this->actingAs(User::factory()->create(['roles' => ['mitglieder']]));
        $this->travelTo('2026-11-15');
        $this->application(['type' => 'jugend', 'birthdate' => '2008-01-01'])->accept(now());
        MembershipFee::generateFor(2027);

        $lists = [
            ListMemberships::class => ['transition', 'open_fees', 'open_donation'],
            ListMembershipFees::class => [],
            ListMembershipApplications::class => ['duplicates'],
        ];

        foreach ($lists as $page => $filters) {
            $test = Livewire::test($page)->assertOk();
            foreach ($filters as $filter) {
                $test->filterTable($filter)->assertOk()->resetTableFilters();
            }
        }

        Livewire::test(ListMemberships::class)
            ->filterTable('transition')
            ->assertSee('Wechsel');
        Livewire::test(ListMembershipFees::class)
            ->filterTable('paid', false)->assertOk()
            ->filterTable('paid', true)->assertOk()
            ->filterTable('year', 2027)->assertOk();
        Livewire::test(ListMemberships::class)
            ->filterTable('active', false)->assertOk()
            ->filterTable('type', 'jugend')->assertOk();
    }

    public function test_willkommens_mail_beim_aufnehmen_und_nachtraeglich(): void
    {
        config(['mail.default' => 'array']);
        Filament::setCurrentPanel(Filament::getPanel('backoffice'));
        $this->actingAs(User::factory()->create(['roles' => ['mitglieder']]));

        // Aufnehmen mit Häkchen (Standard) → Mail an das Mitglied.
        $a = $this->application(['email' => 'valentin@example.test']);
        Livewire::test(EditMembershipApplication::class, ['record' => $a->getKey()])
            ->callAction('accept', ['number' => 5, 'joined_at' => '15.11.2026', 'send_welcome' => true])
            ->assertHasNoActionErrors();

        $log = SentMail::where('mailable', MembershipWelcome::class)->sole();
        $this->assertSame('valentin@example.test', $log->to);
        $this->assertSame('mitglieder', $log->category);
        $this->assertStringContainsString('Mitgliedernummer:</strong> 5', $log->html);
        $this->assertStringNotContainsString('Gönnerbeitrag', $log->html);

        // Ohne Häkchen → keine Mail.
        $b = $this->application(['email' => 'still@example.test']);
        Livewire::test(EditMembershipApplication::class, ['record' => $b->getKey()])
            ->callAction('accept', ['number' => 6, 'joined_at' => '15.11.2026', 'send_welcome' => false])
            ->assertHasNoActionErrors();
        $this->assertSame(1, SentMail::where('mailable', MembershipWelcome::class)->count());

        // Nachträglich beim Mitglied senden.
        $m = Membership::where('number', 6)->sole();
        Livewire::test(EditMembership::class, ['record' => $m->getKey()])
            ->callAction('welcome')
            ->assertHasNoActionErrors();
        $this->assertSame(1, SentMail::where('mailable', MembershipWelcome::class)->where('to', 'still@example.test')->count());
    }

    public function test_willkommens_mail_bei_minderjaehrigen_mit_kopie_an_eltern(): void
    {
        $m = $this->application([
            'type' => 'jugend', 'birthdate' => '2009-06-01', 'email' => 'noah@example.test',
            'guardian_name' => 'Eva', 'guardian_email' => 'eva@example.test',
        ])->accept(now());

        $this->assertSame(['to' => 'noah@example.test', 'cc' => 'eva@example.test'], MembershipMails::welcomeRecipients($m->load('application')));
    }

    public function test_willkommens_mail_dankt_fuer_goennerbeitrag(): void
    {
        $m = $this->application(['donation' => 100])->accept(now());
        $html = (new \App\Mail\MembershipWelcome($m->load('application')))->render();

        $this->assertStringContainsString('Herzlichen Dank für euren Gönnerbeitrag von CHF 100!', $html);
        $this->assertStringContainsString('Gönnerbeitrag:</strong> CHF 100', $html);
    }
}
