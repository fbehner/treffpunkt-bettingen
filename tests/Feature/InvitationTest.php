<?php

namespace Tests\Feature;

use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Mail\AccessGranted;
use App\Mail\NewUserRegistered;
use App\Mail\UserInvitation;
use App\Models\Invitation;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
        $this->seed(ContentSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->admin = User::factory()->create(['name' => 'Frank', 'roles' => ['admin']]);
    }

    /** Einladung über die Verwaltung anlegen und den Link aus der E-Mail holen. */
    private function invite(string $email = 'neu@example.test'): string
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateInvitation::class)
            ->fillForm(['email' => $email, 'name' => 'Nina'])
            ->call('create')
            ->assertHasNoFormErrors();

        $link = null;
        Mail::assertSent(UserInvitation::class, function (UserInvitation $mail) use ($email, &$link) {
            $link = $mail->link;

            return $mail->hasTo($email);
        });
        auth()->logout();

        return parse_url($link, PHP_URL_PATH);
    }

    public function test_ganzer_ablauf_einladen_registrieren_freischalten(): void
    {
        $path = $this->invite();

        $this->get($path)->assertOk()->assertSee('neu@example.test')->assertSee('Konto anlegen');

        $this->post($path, [
            'name' => 'Nina Neu',
            'password' => 'ein-sicheres-passwort',
            'password_confirmation' => 'ein-sicheres-passwort',
        ])->assertRedirect(route('invitation.done'));

        $user = User::where('email', 'neu@example.test')->firstOrFail();
        $this->assertSame([], $user->roles);
        $this->assertNotNull(Invitation::sole()->accepted_at);
        Mail::assertSent(NewUserRegistered::class, fn ($m) => $m->hasTo($this->admin->email));

        // Ohne Rolle kein Zugang.
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/backoffice')->assertForbidden();

        // Webadmin weist Rollen zu → Person wird informiert.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->admin);
        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['roles' => ['mitglieder']])
            ->call('save')
            ->assertHasNoFormErrors();

        Mail::assertSent(AccessGranted::class, fn ($m) => $m->hasTo('neu@example.test'));
        $this->actingAs($user->fresh())->get('/backoffice')->assertOk();
    }

    public function test_link_funktioniert_nur_einmal(): void
    {
        $path = $this->invite();
        $data = ['name' => 'Nina', 'password' => 'ein-sicheres-passwort', 'password_confirmation' => 'ein-sicheres-passwort'];

        $this->post($path, $data)->assertRedirect();
        $this->get($path)->assertOk()->assertSee('schon genutzt');
        $this->post($path, $data)->assertOk()->assertSee('schon genutzt');

        $this->assertSame(1, User::where('email', 'neu@example.test')->count());
    }

    public function test_abgelaufener_oder_falscher_link(): void
    {
        $path = $this->invite();
        $this->travel(Invitation::VALID_DAYS + 1)->days();

        $this->get($path)->assertOk()->assertSee('nicht mehr gültig');
        $this->get('/einladung/falsches-token')->assertOk()->assertSee('nicht mehr gültig');
    }

    public function test_passwort_wird_geprueft(): void
    {
        $path = $this->invite();

        $this->post($path, ['name' => 'Nina', 'password' => 'kurz', 'password_confirmation' => 'kurz'])
            ->assertSessionHasErrors('password');
        $this->post($path, ['name' => 'Nina', 'password' => 'ein-sicheres-passwort', 'password_confirmation' => 'anders-passwort'])
            ->assertSessionHasErrors('password');

        $this->assertSame(0, User::where('email', 'neu@example.test')->count());
    }

    public function test_keine_doppelten_einladungen_oder_konten(): void
    {
        $this->invite('neu@example.test');
        $this->actingAs($this->admin);

        Livewire::test(CreateInvitation::class)
            ->fillForm(['email' => 'neu@example.test'])
            ->call('create')
            ->assertHasFormErrors(['email']);

        Livewire::test(CreateInvitation::class)
            ->fillForm(['email' => $this->admin->email])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_nur_webadmins_duerfen_einladen(): void
    {
        $redaktion = User::factory()->create(['roles' => ['redaktion']]);

        $this->actingAs($redaktion)->get('/admin/invitations')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/invitations')->assertOk();
        $this->actingAs($this->admin)->get('/admin/invitations/create')->assertOk();
    }

    public function test_token_wird_nicht_im_klartext_gespeichert(): void
    {
        $path = $this->invite();
        $token = basename($path);

        $this->assertNotSame($token, Invitation::sole()->token_hash);
        $this->assertSame(hash('sha256', $token), Invitation::sole()->token_hash);
    }
}
