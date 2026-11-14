<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ContentSeeder::class);
    }

    public function test_startseite_zeigt_titel_und_angebote(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ein Ort des')
            ->assertSee('Die Räumlichkeiten sind ein Treffpunkt für alle.')
            ->assertSee('Lesungen')
            ->assertSee('Tanzstunden');
    }

    public function test_ausgeblendete_angebote_erscheinen_nicht(): void
    {
        Offer::where('title', 'Lesungen')->update(['is_visible' => false]);

        $this->get('/')->assertOk()->assertDontSee('Lesungen');
    }

    public function test_geaenderte_texte_erscheinen_auf_der_startseite(): void
    {
        Setting::putMany(['intro' => 'Neuer Einleitungstext.']);

        $this->get('/')->assertSee('Neuer Einleitungstext.');
    }

    public function test_veroeffentlichte_seite_ist_erreichbar_unveroeffentlichte_nicht(): void
    {
        $this->get('/impressum')->assertOk()->assertSee('Impressum');

        Page::where('slug', 'impressum')->update(['is_published' => false]);

        $this->get('/impressum')->assertNotFound();
    }

    public function test_seiteninhalt_wird_bereinigt(): void
    {
        Page::create([
            'title' => 'Test',
            'slug' => 'test',
            'body' => '<p>Hallo</p><script>alert(1)</script>',
            'is_published' => true,
        ]);

        $this->get('/test')->assertOk()->assertSee('Hallo')->assertDontSee('alert(1)', false);
    }

    public function test_seeder_ueberschreibt_keine_bearbeiteten_inhalte(): void
    {
        Page::where('slug', 'impressum')->update(['body' => '<p>Eigener Text</p>']);

        $this->seed(ContentSeeder::class);

        $this->assertSame('<p>Eigener Text</p>', Page::where('slug', 'impressum')->value('body'));
        $this->assertSame(9, Offer::count());
    }

    public function test_redaktion_hat_keinen_zugriff_auf_benutzerverwaltung(): void
    {
        $redaktion = User::factory()->create(['role' => 'redaktion']);

        $this->actingAs($redaktion)->get('/admin/offers')->assertOk();
        $this->actingAs($redaktion)->get('/admin/users')->assertForbidden();
    }

    public function test_deaktivierte_konten_kommen_nicht_in_die_verwaltung(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'is_active' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_sieht_alle_bereiche(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['/admin', '/admin/homepage', '/admin/offers', '/admin/pages', '/admin/users'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
