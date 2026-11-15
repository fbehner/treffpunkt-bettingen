<?php

namespace Tests\Feature;

use App\Filament\Backoffice\Forms\SwissDate;
use App\Filament\Backoffice\Resources\Memberships\Pages\CreateMembership;
use App\Filament\Backoffice\Resources\Memberships\Pages\EditMembership;
use App\Models\Membership;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SwissDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_eingabe_wird_im_schweizer_format_gelesen(): void
    {
        $this->assertSame('1980-03-15', SwissDate::toStorage('15.03.1980'));
        $this->assertSame('1980-03-05', SwissDate::toStorage('5.3.1980'));
        $this->assertNull(SwissDate::toStorage('31.02.1980'));
        $this->assertNull(SwissDate::toStorage('1980'));
        $this->assertSame('15.03.1980', SwissDate::toDisplay('1980-03-15'));
        $this->assertSame('15.03.1980', SwissDate::toDisplay('1980-03-15 00:00:00'));
        $this->assertSame('01.12.2026', SwissDate::toDisplay('2026-11-30T23:00:00.000000Z'));
    }

    public function test_mitglied_mit_eingetipptem_geburtsdatum_erfassen_und_bearbeiten(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('backoffice'));
        $this->actingAs(User::factory()->create(['roles' => ['mitglieder']]));

        Livewire::test(CreateMembership::class)
            ->fillForm([
                'number' => 7,
                'type' => 'einzel',
                'fee' => 50,
                'joined_at' => '01.12.2026',
                'people' => [
                    ['relation' => 'haupt', 'first_name' => 'Anna', 'last_name' => 'Test', 'birthdate' => '15.03.1980'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $m = Membership::where('number', 7)->firstOrFail();
        $this->assertSame('2026-12-01', $m->joined_at->toDateString());
        $this->assertSame('1980-03-15', $m->people->first()->birthdate->toDateString());
        $this->assertSame(1980, $m->people->first()->birth_year);

        // Beim Öffnen wieder als TT.MM.JJJJ.
        $state = Livewire::test(EditMembership::class, ['record' => $m->getKey()])->get('data');
        $this->assertSame('01.12.2026', $state['joined_at']);
        $this->assertSame('15.03.1980', collect($state['people'])->first()['birthdate']);
    }

    public function test_ungueltiges_datum_wird_abgelehnt(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('backoffice'));
        $this->actingAs(User::factory()->create(['roles' => ['mitglieder']]));

        Livewire::test(CreateMembership::class)
            ->fillForm([
                'number' => 8, 'type' => 'einzel', 'fee' => 50, 'joined_at' => '31.02.2026',
                'people' => [['relation' => 'haupt', 'first_name' => 'Ben']],
            ])
            ->call('create')
            ->assertHasFormErrors(['joined_at']);
    }
}
