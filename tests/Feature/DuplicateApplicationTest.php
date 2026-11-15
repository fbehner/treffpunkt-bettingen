<?php

namespace Tests\Feature;

use App\Filament\Backoffice\Resources\MembershipApplications\Pages\EditMembershipApplication;
use App\Filament\Backoffice\Resources\MembershipApplications\Pages\ListMembershipApplications;
use App\Mail\NewMembershipApplication;
use App\Models\MembershipApplication;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DuplicateApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(ContentSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('backoffice'));
        $this->actingAs(User::factory()->create(['roles' => ['mitglieder']]));
    }

    private function application(array $overrides = []): MembershipApplication
    {
        return MembershipApplication::create(array_merge([
            'type' => 'einzel', 'source' => 'web', 'received_at' => '2026-11-14', 'fee' => 50,
            'first_name' => 'Hans', 'last_name' => 'Muster', 'street' => 'Hauptstrasse 1',
            'zip_city' => '4126 Bettingen', 'email' => 'hans@example.test', 'status' => 'eingegangen',
        ], $overrides));
    }

    public function test_erkennt_gleiche_email_und_gleichen_namen(): void
    {
        $first = $this->application();
        $sameMail = $this->application(['first_name' => 'Johann', 'email' => 'HANS@example.test']);
        $sameName = $this->application(['email' => 'anders@example.test']);
        $other = $this->application(['first_name' => 'Eva', 'email' => 'eva@example.test']);

        $this->assertTrue($sameMail->hasPossibleDuplicates());
        $this->assertTrue($sameName->hasPossibleDuplicates());
        $this->assertFalse($other->hasPossibleDuplicates());
        $this->assertEqualsCanonicalizing([$first->id], $sameMail->possibleDuplicates()['applications']->pluck('id')->all());
    }

    public function test_erkennt_bestehende_mitgliedschaft(): void
    {
        $first = $this->application();
        $membership = $first->accept(Carbon::parse('2026-11-15'));
        $second = $this->application();

        $found = $second->possibleDuplicates();
        $this->assertSame([$membership->id], $found['memberships']->pluck('id')->all());
        // Das angenommene Gesuch sieht seine eigene Mitgliedschaft nicht als Duplikat.
        $this->assertTrue($first->fresh()->possibleDuplicates()['memberships']->isEmpty());
    }

    public function test_als_doppelt_erledigen_ohne_mail_und_mit_verweis(): void
    {
        Mail::fake();
        $first = $this->application();
        $membership = $first->accept(Carbon::parse('2026-11-15'));
        $second = $this->application(['received_at' => '2026-11-15']);

        Livewire::test(EditMembershipApplication::class, ['record' => $second->getKey()])
            ->assertSee('Mögliches Duplikat')
            ->callAction('markDuplicate', ['original_id' => $first->id])
            ->assertHasNoActionErrors();

        $second->refresh();
        $this->assertSame('doppelt', $second->status);
        $this->assertSame($first->id, $second->duplicate_of_id);
        $this->assertStringContainsString('Mitglied Nr. '.$membership->number, $second->notes);
        Mail::assertNothingSent();

        // Zählt nicht mehr als offen und nicht mehr als Duplikat.
        $this->assertFalse($first->fresh()->hasPossibleDuplicates());
        $this->assertSame(0, MembershipApplication::whereIn('status', ['eingegangen', 'bestaetigung'])->count());
    }

    public function test_liste_markiert_und_filtert_duplikate(): void
    {
        $this->application();
        $dup = $this->application(['received_at' => '2026-11-15']);
        $alone = $this->application(['first_name' => 'Eva', 'last_name' => 'Solo', 'email' => 'eva@example.test']);

        Livewire::test(ListMembershipApplications::class)
            ->assertSee('Doppelt?')
            ->filterTable('duplicates')
            ->assertCanSeeTableRecords([$dup])
            ->assertCanNotSeeTableRecords([$alone]);
    }

    public function test_vorstandsmail_warnt_bei_duplikat(): void
    {
        $this->application();
        $second = $this->application();

        $html = (new NewMembershipApplication($second))->render();
        $this->assertStringContainsString('mögliches Duplikat', $html);

        $alone = $this->application(['first_name' => 'Eva', 'last_name' => 'Solo', 'email' => 'eva@example.test']);
        $this->assertStringNotContainsString('mögliches Duplikat', (new NewMembershipApplication($alone))->render());
    }
}
