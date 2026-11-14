<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Startinhalte nach dem Plakat. Legt nur an, was noch fehlt –
 * bereits bearbeitete Inhalte werden nie überschrieben.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        if (Offer::query()->doesntExist()) {
            $offers = [
                ['Lesungen', 'Autorinnen und Autoren lesen – bei Kaffee und Kuchen.', 'book', 'rost'],
                ['Poetry Slam & Theater', 'Bühne frei für Worte, Spiel und viel Applaus.', 'mic', 'senf'],
                ['Kasperletheater', 'Puppenspiel für Kinder – und alle, die gerne mitlachen.', 'puppet', 'gruen'],
                ['Science für die Leute', 'Forschung verständlich erklärt. Fragen erwünscht.', 'flask', 'dunkel'],
                ['Die Arena', 'Politdiskussionen mit Respekt – aktuelle Themen, offene Runde.', 'chat', 'beige'],
                ['Kinonachmittag', 'Klassiker und Entdeckungen auf der Leinwand.', 'film', 'rost'],
                ['Saisonale Events', 'Adventskränze basteln, Ostereier malen und mehr.', 'egg', 'senf'],
                ['Live-Musik', 'Vom Kammerorchester bis zum Solokonzert.', 'music', 'gruen'],
                ['Tanzstunden', 'Erste Schritte für Neugierige, neue Figuren für Geübte.', 'dance', 'dunkel'],
            ];

            foreach ($offers as $i => [$title, $description, $icon, $color]) {
                Offer::create(compact('title', 'description', 'icon', 'color') + ['sort' => $i + 1]);
            }
        }

        $pages = [
            'ueber-uns' => [
                'title' => 'Über uns',
                'show_in_nav' => true,
                'show_in_footer' => false,
                'is_published' => true,
                'sort' => 1,
                'body' => '<p>Wir sind ein Verein, der in Bettingen einen Ort für Begegnung schaffen will: ein Café und einen Treffpunkt für alle Generationen.</p>'
                    .'<h2>Was wir vorhaben</h2><p>[Hier kurz beschreiben: Wer seid ihr, seit wann gibt es den Verein, was ist der Stand der Planung?]</p>'
                    .'<h2>Mitmachen</h2><p>[Hier beschreiben, wie man sich einbringen kann.]</p>',
            ],
            'impressum' => [
                'title' => 'Impressum',
                'show_in_nav' => false,
                'show_in_footer' => true,
                'is_published' => true,
                'sort' => 90,
                'body' => '<h2>Verantwortlich für den Inhalt</h2><p>[Vereinsname]<br>[Strasse Nr.]<br>[PLZ] Bettingen<br>Schweiz</p>'
                    .'<p>E-Mail: [E-MAIL]</p><p>Vertreten durch: [Name, Funktion im Vorstand]</p>',
            ],
            'datenschutz' => [
                'title' => 'Datenschutz',
                'show_in_nav' => false,
                'show_in_footer' => true,
                'is_published' => true,
                'sort' => 91,
                'body' => '<p>[Datenschutzerklärung nach dem Schweizer Datenschutzgesetz (revDSG) – vor der Veröffentlichung ergänzen. Sie muss u. a. nennen: wer verantwortlich ist, welche Daten erhoben werden (z. B. bei Anmeldung und Raumreservierung), wozu, wie lange sie gespeichert werden, wo die Website gehostet ist (green.ch, Schweiz) und welche Rechte Betroffene haben.]</p>',
            ],
        ];

        foreach ($pages as $slug => $attributes) {
            Page::firstOrCreate(['slug' => $slug], $attributes);
        }
    }
}
