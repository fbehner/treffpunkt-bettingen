<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(ContentSeeder::class);
    }

    public function test_startseite_zeigt_alle_bereiche(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ein Ort des')
            ->assertSee('Einkaufen und einkehren.')
            ->assertSee('Spielen und treffen.')
            ->assertSee('Lesungen')
            ->assertSee('Wo wir stehen.')
            ->assertSee('Einwohnergemeindeversammlung am 8. Dezember 2026');
    }

    public function test_hauptmenue_enthaelt_die_neuen_seiten(): void
    {
        $this->get('/')
            ->assertSeeInOrder(['Das Haus', 'Projekt', 'Verein', 'Aktuelles', 'Mitglied werden']);
    }

    public function test_unterseiten_sind_erreichbar(): void
    {
        foreach (['/das-haus' => 'Hauptstrasse 85.', '/projekt' => 'Wer was trägt.', '/verein' => 'Was wir tun.', '/impressum' => 'Impressum', '/datenschutz' => 'Datenschutz'] as $url => $text) {
            $this->get($url)->assertOk()->assertSee($text);
        }
    }

    public function test_startseite_unter_eigener_adresse_leitet_um(): void
    {
        $this->get('/startseite')->assertRedirect('/');
    }

    public function test_bilder_und_dokumente_werden_bereitgestellt(): void
    {
        Storage::disk('public')->assertExists('bilder/cafe.jpg');
        Storage::disk('public')->assertExists('dokumente/statuten-treffpunkt-bettingen.pdf');
        Storage::disk('public')->assertExists('dokumente/einrichtungsplan-eg-ug-entwurf-v4.pdf');
    }

    public function test_ausgeblendete_angebote_erscheinen_nicht(): void
    {
        Offer::where('title', 'Lesungen')->update(['is_visible' => false]);

        $this->get('/')->assertOk()->assertDontSee('Lesungen');
    }

    public function test_hinweisband_laesst_sich_ausblenden(): void
    {
        Setting::putMany(['banner_text' => '']);

        $this->get('/')->assertDontSee('Nächster Schritt');
    }

    public function test_unveroeffentlichte_seite_ist_nicht_erreichbar(): void
    {
        Page::where('slug', 'impressum')->update(['is_published' => false]);

        $this->get('/impressum')->assertNotFound();
    }

    public function test_seiteninhalt_wird_bereinigt(): void
    {
        Page::create([
            'title' => 'Test',
            'slug' => 'test',
            'body' => '<p>Hallo</p><script>alert(1)</script>',
            'blocks' => [['type' => 'text', 'data' => ['heading' => 'Block', 'body' => '<p>Text</p><img src=x onerror=alert(2)>']]],
            'is_published' => true,
        ]);

        $this->get('/test')->assertOk()->assertSee('Hallo')
            ->assertDontSee('alert(1)', false)
            ->assertDontSee('onerror', false);
    }

    public function test_seeder_ueberschreibt_keine_bearbeiteten_inhalte(): void
    {
        Page::where('slug', 'impressum')->update(['body' => '<p>Eigener Text</p>']);
        Page::where('slug', 'das-haus')->update(['lead' => 'Eigene Einleitung']);

        $this->seed(ContentSeeder::class);

        $this->assertSame('<p>Eigener Text</p>', Page::where('slug', 'impressum')->value('body'));
        $this->assertSame('Eigene Einleitung', Page::where('slug', 'das-haus')->value('lead'));
        $this->assertSame(9, Offer::count());
    }

    public function test_alte_platzhalterseiten_werden_ersetzt(): void
    {
        Page::where('slug', 'datenschutz')->update(['body' => '<p>[Datenschutzerklärung – ergänzen]</p>']);
        Page::create(['title' => 'Über uns', 'slug' => 'ueber-uns', 'body' => '<p>[Hier kurz beschreiben: …]</p>', 'is_published' => true, 'show_in_nav' => true]);

        $this->seed(ContentSeeder::class);

        $this->assertStringContainsString('Welche Daten wir bearbeiten', Page::where('slug', 'datenschutz')->value('body'));
        $this->assertFalse(Page::where('slug', 'ueber-uns')->value('is_published'));
    }

    public function test_aktuelles_zeigt_termine_und_beitraege(): void
    {
        $this->travelTo('2026-11-20 10:00');

        $this->get('/aktuelles')
            ->assertOk()
            ->assertSee('Einwohnergemeindeversammlung: Grundsatzentscheid zum Treffpunkt')
            ->assertSee('Verein Treffpunkt Bettingen gegründet');

        $this->get('/aktuelles/verein-treffpunkt-bettingen-gegruendet')->assertOk();
    }

    public function test_unveroeffentlichte_beitraege_sind_versteckt(): void
    {
        Post::where('slug', 'verein-treffpunkt-bettingen-gegruendet')->update(['is_published' => false]);

        $this->get('/aktuelles/verein-treffpunkt-bettingen-gegruendet')->assertNotFound();
    }

    public function test_redaktion_hat_keinen_zugriff_auf_benutzer_und_gesuche(): void
    {
        $redaktion = User::factory()->create(['roles' => ['redaktion']]);

        $this->actingAs($redaktion)->get('/admin/pages')->assertOk();
        $this->actingAs($redaktion)->get('/admin/users')->assertForbidden();
        $this->actingAs($redaktion)->get('/backoffice')->assertForbidden();
    }

    public function test_deaktivierte_konten_kommen_nicht_in_die_verwaltung(): void
    {
        $user = User::factory()->create(['roles' => ['admin'], 'is_active' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_sieht_alle_bereiche(): void
    {
        $admin = User::factory()->create(['roles' => ['admin']]);
        $home = Page::where('slug', Page::HOME_SLUG)->first();

        foreach (['/admin', '/admin/einstellungen', '/admin/offers', '/admin/pages', "/admin/pages/{$home->id}/edit", '/admin/posts', '/admin/users'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
