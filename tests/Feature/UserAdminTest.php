<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_webadmin_kann_sich_selbst_die_mitgliederverwaltung_geben(): void
    {
        $me = User::factory()->create(['roles' => ['admin']]);
        $this->actingAs($me);

        Livewire::test(EditUser::class, ['record' => $me->getKey()])
            ->fillForm(['roles' => ['admin', 'mitglieder']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['admin', 'mitglieder'], $me->fresh()->roles);
        $this->actingAs($me->fresh())->get('/backoffice')->assertOk();
    }

    public function test_eigenes_webadmin_recht_kann_nicht_entfernt_werden(): void
    {
        $me = User::factory()->create(['roles' => ['admin']]);
        $this->actingAs($me);

        Livewire::test(EditUser::class, ['record' => $me->getKey()])
            ->fillForm(['roles' => ['redaktion']])
            ->call('save')
            ->assertHasFormErrors(['roles']);

        $this->assertSame(['admin'], $me->fresh()->roles);
    }

    public function test_andere_konten_frei_aendern(): void
    {
        $me = User::factory()->create(['roles' => ['admin']]);
        $other = User::factory()->create(['roles' => ['admin']]);
        $this->actingAs($me);

        Livewire::test(EditUser::class, ['record' => $other->getKey()])
            ->fillForm(['roles' => ['mitglieder']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['mitglieder'], $other->fresh()->roles);
    }

    public function test_fehlermeldungen_sind_deutsch(): void
    {
        app()->setLocale('de');

        $this->assertSame('Der gewählte Wert für Rollen ist ungültig.', __('validation.in', ['attribute' => 'Rollen']));
        $this->assertNotSame('passwords.sent', __('passwords.sent'));
    }
}
