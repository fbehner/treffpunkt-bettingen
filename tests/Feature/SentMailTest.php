<?php

namespace Tests\Feature;

use App\Mail\GuardianConfirmation;
use App\Mail\UserInvitation;
use App\Models\Invitation;
use App\Models\MembershipApplication;
use App\Models\SentMail;
use App\Models\User;
use App\Support\Invitations;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Hier wird wirklich „versendet“ (Mailer „array“), damit das Protokoll greift.
 */
class SentMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array']);
        Storage::fake('public');
        $this->seed(ContentSeeder::class);
    }

    private function invitation(): Invitation
    {
        return Invitation::create([
            'email' => 'nina@example.test',
            'token_hash' => str_repeat('0', 64),
            'expires_at' => now(),
            'invited_by' => User::factory()->create(['roles' => ['admin']])->id,
        ]);
    }

    public function test_einladung_wird_protokolliert_ohne_zugangsschluessel(): void
    {
        $invitation = $this->invitation();
        $this->assertTrue(Invitations::send($invitation));

        $log = SentMail::sole();
        $this->assertSame('konto', $log->category);
        $this->assertSame(UserInvitation::class, $log->mailable);
        $this->assertSame('nina@example.test', $log->to);
        $this->assertStringContainsString('Konto anlegen', $log->html);
        $this->assertStringContainsString('/einladung/[ausgeblendet]', $log->html);
        $this->assertDoesNotMatchRegularExpression('~/einladung/[A-Za-z0-9]{20,}~', $log->html);
    }

    public function test_erneut_senden_haelt_einladung_gueltig(): void
    {
        $invitation = $this->invitation();
        Invitations::send($invitation);
        $this->travel(Invitation::VALID_DAYS + 2)->days();
        $this->assertSame('abgelaufen', $invitation->fresh()->status());

        Invitations::send($invitation->fresh());

        $this->assertSame('offen', $invitation->fresh()->status());
        $this->assertTrue($invitation->fresh()->expires_at->isAfter(now()->addDays(Invitation::VALID_DAYS - 1)));
        $this->assertSame(2, SentMail::count());
    }

    public function test_beitrittsgesuch_mails_sind_im_backoffice_mit_ausgeblendeter_signatur(): void
    {
        $this->travelTo('2026-11-15');
        $this->post('/mitglied-werden', [
            'type' => 'jugend', 'first_name' => 'Noah', 'last_name' => 'Test', 'birthdate' => '2009-06-01',
            'street' => 'Weg 1', 'zip_city' => '4126 Bettingen', 'email' => 'noah@example.test',
            'guardian_name' => 'Eva Test', 'guardian_email' => 'eva@example.test',
            'statutes' => '1', 'privacy' => '1',
        ])->assertRedirect();

        $this->assertSame(2, SentMail::where('category', 'mitglieder')->count());
        $guardian = SentMail::where('mailable', GuardianConfirmation::class)->sole();
        $this->assertStringContainsString('signature=[ausgeblendet]', $guardian->html);
        $this->assertDoesNotMatchRegularExpression('/signature=[a-f0-9]{32,}/', $guardian->html);
        $this->assertStringContainsString('/mitglied-werden/bestaetigen/'.MembershipApplication::sole()->id, $guardian->html);
    }

    public function test_sichtbarkeit_je_bereich(): void
    {
        Invitations::send($this->invitation());
        Mail::raw('Test', fn ($m) => $m->to('x@example.test')->subject('Testmail'));
        SentMail::create(['category' => 'mitglieder', 'to' => 'm@example.test', 'subject' => 'Gesuch Geheim', 'sent_at' => now()]);

        $webadmin = User::factory()->create(['roles' => ['admin']]);
        $mitglieder = User::factory()->create(['roles' => ['mitglieder']]);

        $this->actingAs($webadmin)->get('/admin/sent-mails')
            ->assertOk()->assertSee('nina@example.test')->assertSee('Testmail')->assertDontSee('Gesuch Geheim');

        $mail = SentMail::where('category', 'mitglieder')->first();
        $this->actingAs($webadmin)->get('/admin/sent-mails/'.$mail->id)->assertNotFound();

        $this->actingAs($mitglieder)->get('/backoffice/sent-mails')
            ->assertOk()->assertSee('Gesuch Geheim')->assertDontSee('nina@example.test');
        $this->actingAs($mitglieder)->get('/backoffice/sent-mails/'.$mail->id)->assertOk();
    }

    public function test_alte_eintraege_werden_geloescht(): void
    {
        SentMail::create(['category' => 'sonstige', 'to' => 'alt@example.test', 'sent_at' => now()]);
        $this->travel(13)->months();
        SentMail::create(['category' => 'sonstige', 'to' => 'neu@example.test', 'sent_at' => now()]);

        Artisan::call('model:prune', ['--model' => [SentMail::class]]);

        $this->assertSame(['neu@example.test'], SentMail::pluck('to')->all());
    }
}
