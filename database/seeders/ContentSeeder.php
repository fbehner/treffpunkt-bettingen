<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Startinhalte aus den Projektunterlagen (Beilage EGV, Statuten,
 * Einrichtungsplan, Ablaufplakat). Läuft bei jedem Deploy, legt aber nur
 * an, was fehlt – bearbeitete Inhalte werden nie überschrieben.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->copyAssets();
        $this->offers();
        $this->pages();
        $this->retirePlaceholders();
        $this->posts();
    }

    /** Bilder und PDFs in den öffentlichen Speicher kopieren (falls nicht vorhanden). */
    private function copyAssets(): void
    {
        $disk = Storage::disk('public');
        $files = [
            'cafe.jpg' => 'bilder/cafe.jpg',
            'dorfladen.jpg' => 'bilder/dorfladen.jpg',
            'ug-familien.jpg' => 'bilder/ug-familien.jpg',
            'ug-jugend.jpg' => 'bilder/ug-jugend.jpg',
            'statuten-treffpunkt-bettingen.pdf' => 'dokumente/statuten-treffpunkt-bettingen.pdf',
            'einrichtungsplan-eg-ug-entwurf-v4.pdf' => 'dokumente/einrichtungsplan-eg-ug-entwurf-v4.pdf',
            'beilage-egv-8-dezember-2026-entwurf.pdf' => 'dokumente/beilage-egv-8-dezember-2026-entwurf.pdf',
        ];

        foreach ($files as $source => $target) {
            $path = database_path('seeders/assets/'.$source);
            if (! $disk->exists($target) && is_file($path)) {
                $disk->put($target, file_get_contents($path));
            }
        }
    }

    private function offers(): void
    {
        if (Offer::query()->exists()) {
            return;
        }

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

    private function pages(): void
    {
        foreach ($this->pageDefinitions() as $slug => $attributes) {
            Page::firstOrCreate(['slug' => $slug], $attributes);
        }
    }

    /**
     * Seiten aus der ersten Version, die noch unbearbeitete Platzhalter
     * enthalten, durch die neuen Inhalte ersetzen bzw. ausblenden.
     */
    private function retirePlaceholders(): void
    {
        $definitions = $this->pageDefinitions();

        Page::query()->where('slug', 'ueber-uns')
            ->where('body', 'like', '%[Hier kurz beschreiben%')
            ->update(['is_published' => false, 'show_in_nav' => false]);

        foreach (['impressum' => '[Vereinsname]', 'datenschutz' => '[Datenschutzerklärung'] as $slug => $marker) {
            Page::query()->where('slug', $slug)
                ->where('body', 'like', '%'.$marker.'%')
                ->update(['body' => $definitions[$slug]['body']]);
        }
    }

    private function posts(): void
    {
        $posts = [
            'gemeindeversammlung-8-dezember-2026' => [
                'title' => 'Einwohnergemeindeversammlung: Grundsatzentscheid zum Treffpunkt',
                'kind' => 'event',
                'event_starts_at' => '2026-12-08 00:00:00',
                'excerpt' => 'Die Versammlung entscheidet, ob das Konzept für Dorfladen, Café und Treffpunkt an der Hauptstrasse 85 weiterverfolgt wird.',
                'body' => '<p>Am 8. Dezember 2026 entscheidet die Einwohnergemeindeversammlung über eine Grundsatzfrage: Soll das Konzept der Kerngruppe für einen Dorfladen mit Café und Begegnungsort an der Hauptstrasse 85 weiterverfolgt werden?</p><p>Alle Informationen zum Konzept, zu den Rollen von Gemeinde, Verein und Betreiberin sowie zum weiteren Zeitplan findet ihr auf der Seite <a href="/projekt">Projekt</a>.</p>',
                'published_at' => '2026-11-15 08:00:00',
                'is_published' => true,
            ],
            'infoveranstaltung-14-november-2026' => [
                'title' => 'Infoveranstaltung in der Baslerhof-Scheune',
                'kind' => 'event',
                'event_starts_at' => '2026-11-14 00:00:00',
                'event_location' => 'Baslerhof-Scheune, Bettingen',
                'excerpt' => 'Die Kerngruppe hat das Konzept für den Treffpunkt der ganzen Bevölkerung vorgestellt.',
                'body' => '<p>Am 14. November 2026 hat die Kerngruppe das Konzept für den Treffpunkt an der Hauptstrasse 85 öffentlich in der Baslerhof-Scheune vorgestellt.</p><p>Die Unterlagen zur Gemeindeversammlung vom 8. Dezember findet ihr auf der Seite <a href="/projekt">Projekt</a>.</p>',
                'published_at' => '2026-11-14 20:00:00',
                'is_published' => true,
            ],
            'verein-treffpunkt-bettingen-gegruendet' => [
                'title' => 'Verein Treffpunkt Bettingen gegründet',
                'kind' => 'news',
                'excerpt' => 'Aus dem Partizipationsprozess ist ein Trägerverein entstanden. Er soll den Treffpunkt in der Bevölkerung verankern und über die Aufbauphase hinaus tragen.',
                'body' => '<p>Am 2. November 2026 wurde der Verein Treffpunkt Bettingen gegründet. Er ist aus dem Partizipationsprozess entstanden, zu dem seit Februar 2026 die ganze Bevölkerung eingeladen war.</p><p>Der Verein stellt Räume für Begegnungen zur Verfügung, organisiert die Buchung und kümmert sich um Infrastruktur und Sauberkeit. Die Aktivitäten selbst kommen aus der Bevölkerung.</p><p><a href="/mitglied-werden">Jetzt Mitglied werden</a></p>',
                'published_at' => '2026-11-02 18:00:00',
                'is_published' => true,
            ],
        ];

        foreach ($posts as $slug => $attributes) {
            Post::firstOrCreate(['slug' => $slug], $attributes);
        }
    }

    /** Ein Baustein im Format des Filament-Builders. */
    private static function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => $data];
    }

    private function pageDefinitions(): array
    {
        return [
            Page::HOME_SLUG => [
                'title' => 'Startseite',
                'is_published' => true,
                'show_in_nav' => false,
                'sort' => 0,
                'blocks' => [
                    self::block('hero', [
                        'kicker' => 'Dorfladen & Café · Hauptstrasse 85 · in Planung',
                        'headline' => 'Ein Ort des',
                        'headline_accent' => 'aktiven Treffens.',
                        'lead' => 'In Bettingen fehlt ein Dorftreffpunkt. An der Hauptstrasse 85 soll er entstehen: ein Dorfladen mit Café, tagsüber belebt – und am Abend offen für Vereine, Gruppen und alle, die etwas vorhaben.',
                        'image' => 'bilder/cafe.jpg',
                        'image_alt' => 'Visualisierung des Cafés mit geschwungener Theke und Holztischen',
                        'image_caption' => 'Stimmungsbild – keine Bauplanung',
                        'primary_label' => 'So soll das Haus werden',
                        'primary_url' => '/das-haus',
                        'secondary_label' => 'Mitglied werden',
                        'secondary_url' => '/mitglied-werden',
                    ]),
                    self::block('cards', [
                        'kicker' => 'Erdgeschoss · das Basisangebot',
                        'heading' => 'Einkaufen und einkehren.',
                        'background' => 'creme',
                        'items' => [
                            [
                                'title' => 'Dorfladen',
                                'text' => 'Produkte des täglichen Bedarfs – mit Schwerpunkt auf frischen, lokalen und regionalen Produkten. Das stärkt die Wertschöpfung in der Region.',
                                'image' => 'bilder/dorfladen.jpg',
                                'image_alt' => 'Visualisierung des Dorfladens mit Marktstand und Kühlregalen',
                                'link_label' => 'Mehr zum Dorfladen',
                                'link_url' => '/das-haus#eg',
                            ],
                            [
                                'title' => 'Café',
                                'text' => 'Das Herzstück: ein offener, heimeliger Treffpunkt für Znüni, Mittag oder einen Kaffee zwischendurch – für Jung und Alt. Klimatisiert, also auch ein kühler Ort an heissen Tagen.',
                                'image' => 'bilder/cafe.jpg',
                                'image_alt' => 'Visualisierung des Cafés mit Holztischen und Regalen mit Hofprodukten',
                                'link_label' => 'Mehr zum Café',
                                'link_url' => '/das-haus#cafe',
                            ],
                        ],
                    ]),
                    self::block('cards', [
                        'kicker' => 'Untergeschoss',
                        'heading' => 'Spielen und treffen.',
                        'background' => 'beige',
                        'items' => [
                            [
                                'title' => 'Familien & Kinder',
                                'text' => 'Spielturm, Krabbelzone und eine Leseecke für Eltern – ein Ort für Kinder und Familien, gerade in der kalten Jahreszeit.',
                                'image' => 'bilder/ug-familien.jpg',
                                'image_alt' => 'Visualisierung des Familien- und Spielbereichs mit Spielturm',
                            ],
                            [
                                'title' => 'Jugend, Gruppen & Kurse',
                                'text' => 'Ein vielseitiger Raum für Kurse und Vereine. Am Samstagabend, etwa 19.00 bis 22.30 Uhr, als betreuter Jugendtreff mitten im Dorf.',
                                'image' => 'bilder/ug-jugend.jpg',
                                'image_alt' => 'Visualisierung des Gruppen- und Jugendraums mit Töggelikasten',
                            ],
                        ],
                    ]),
                    self::block('columns', [
                        'heading' => 'Ein Haus, das den ganzen Tag lebt.',
                        'items' => [
                            ['label' => 'Tagsüber', 'tone' => 'senf', 'title' => 'Laden & Café offen', 'text' => 'Die Betreiberin führt Dorfladen und Café. Spontane Begegnungen für alle.'],
                            ['label' => 'Abends', 'tone' => 'gruen', 'title' => 'Der Verein übernimmt', 'text' => 'Die Caféfläche ist reservierbar für Kurse, Sitzungen, Treffen und Veranstaltungen.'],
                            ['label' => 'Samstagabend', 'tone' => 'dunkel', 'title' => 'Jugendtreff', 'text' => 'Im Untergeschoss, unter Aufsicht – ein geschützter Treffpunkt für die Bettinger Jugend.'],
                        ],
                    ]),
                    self::block('offers', [
                        'kicker' => 'Aus der Bevölkerung, für die Bevölkerung',
                        'heading' => 'Das alles könnte hier stattfinden.',
                        'intro' => 'Der Verein organisiert keine eigenen Anlässe. Er stellt die Räume, kümmert sich um Buchung und Sauberkeit – die Ideen kommen von euch.',
                        'closing_dark' => 'Auch für eure',
                        'closing_accent' => 'Ideen.',
                        'primary_label' => 'Idee einbringen',
                        'primary_url' => 'mailto:info@treffpunkt-bettingen.ch?subject=Idee%20f%C3%BCr%20den%20Treffpunkt',
                    ]),
                    self::block('steps', [
                        'kicker' => 'Projektstand',
                        'heading' => 'Wo wir stehen.',
                        'layout' => 'row',
                        'background' => 'dunkel',
                        'anchor' => 'stand',
                        'items' => $this->milestones(short: true),
                        'link_label' => 'Alles zum Projekt, zu den Rollen und zur Finanzierung',
                        'link_url' => '/projekt',
                    ]),
                    self::block('membership', [
                        'kicker' => 'Der Trägerverein',
                        'heading' => 'Wir schaffen Raum für eure Ideen.',
                        'body' => $this->purposeList(),
                        'box_heading' => 'Mitglied werden',
                        'box_text' => 'Mitglieder tragen das Projekt mit, nutzen die Räume zu vergünstigten Tarifen und haben Vorrang bei Reservationen.',
                        'button_label' => 'Beitrittsgesuch ausfüllen',
                    ]),
                ],
            ],

            'das-haus' => [
                'title' => 'Das Haus',
                'kicker' => 'Das Haus',
                'headline' => 'Hauptstrasse 85.',
                'headline_accent' => 'Mitten im Dorf.',
                'lead' => 'Die Liegenschaft gehört der Gemeinde und wird teilsaniert. Im Erdgeschoss sollen Dorfladen und Café einziehen, im Untergeschoss Räume für Familien, Gruppen und die Jugend. Ein neuer Lift macht beide Geschosse hindernisfrei zugänglich.',
                'meta_description' => 'Dorfladen und Café im Erdgeschoss, Räume für Familien, Jugend und Gruppen im Untergeschoss: so soll das Haus an der Hauptstrasse 85 werden.',
                'is_published' => true,
                'show_in_nav' => true,
                'sort' => 1,
                'blocks' => [
                    self::block('facts', [
                        'items' => [
                            ['value' => '45.8 m²', 'label' => 'Dorfladen', 'tone' => 'senf'],
                            ['value' => '89.1 m²', 'label' => 'Café und Treffpunkt', 'tone' => 'rost'],
                            ['value' => 'rund 42', 'label' => 'Sitzplätze im Café', 'tone' => 'gruen'],
                            ['value' => '57.4 m²', 'label' => 'Untergeschoss, zwei Räume', 'tone' => 'dunkel'],
                        ],
                        'note' => 'Flächen laut Architektenplan, Einrichtungskonzept Entwurf vom 15. Oktober 2026.',
                    ]),
                    self::block('media', [
                        'kicker' => 'Erdgeschoss',
                        'heading' => 'Dorfladen',
                        'body' => '<p>Die Grundversorgung im Dorf – mit Schwerpunkt auf frischen, lokalen und regionalen Produkten.</p><ul><li>Marktstand für Früchte und Gemüse</li><li>Kühlnische «Frisch &amp; Regional»</li><li>Trockensortiment, Getränke, Haushalt</li><li>Kasse an der Kaffee-Bar</li></ul>',
                        'image' => 'bilder/dorfladen.jpg',
                        'image_alt' => 'Visualisierung Dorfladen: Wandregale, Marktstand, Kühlschrankreihe',
                        'image_side' => 'left',
                        'anchor' => 'eg',
                        'anchor_label' => 'Erdgeschoss',
                    ]),
                    self::block('media', [
                        'kicker' => 'Erdgeschoss',
                        'heading' => 'Café',
                        'body' => '<p>Tagsüber Treffpunkt für Znüni, Mittag und Kaffee. Abends Raum für Kurse, Sitzungen und Veranstaltungen – reservierbar über den Verein.</p><ul><li>Kaffee-Bar mit Frischtheke</li><li>Café- und Bistrotische, rund 42 Plätze</li><li>Regale mit Hofprodukten und Brot</li><li>Klimatisiert – ein kühler Ort an heissen Tagen</li></ul>',
                        'image' => 'bilder/cafe.jpg',
                        'image_alt' => 'Visualisierung Café: geschwungene Theke, Holztische, Regale mit Hofprodukten',
                        'image_side' => 'right',
                        'anchor' => 'cafe',
                    ]),
                    self::block('cards', [
                        'kicker' => 'Untergeschoss',
                        'heading' => 'Spielen, lernen, treffen.',
                        'intro' => 'Ein Raum, durch eine neue Wand mit Tür in zwei Bereiche geteilt.',
                        'background' => 'beige',
                        'anchor' => 'ug',
                        'anchor_label' => 'Untergeschoss',
                        'items' => [
                            [
                                'title' => 'Familien & Kinder',
                                'text' => 'Spielturm mit Rutsche und Bällebad, Krabbel- und Polsterzone, Spielteppich mit Lego-Tisch. Für die Eltern: Sofa, Leseecke und ein Tisch mit Blick auf den Spielbereich.',
                                'image' => 'bilder/ug-familien.jpg',
                                'image_alt' => 'Visualisierung Familien- und Spielbereich',
                            ],
                            [
                                'title' => 'Jugend, Vereine & Kurse',
                                'text' => 'Kurs- und Gruppentisch, Töggelikasten, Flipper und eine Lounge. Am Samstagabend etwa 19.00 bis 22.30 Uhr betreuter Jugendtreff – als Ergänzung zum offenen Jugendtreff am Mittwochabend.',
                                'image' => 'bilder/ug-jugend.jpg',
                                'image_alt' => 'Visualisierung Gruppen- und Jugendraum',
                            ],
                        ],
                    ]),
                    self::block('columns', [
                        'kicker' => 'Für alle',
                        'heading' => 'Offen, hindernisfrei, mit klaren Regeln.',
                        'anchor' => 'fuer-alle',
                        'anchor_label' => 'Für alle zugänglich',
                        'items' => [
                            ['tone' => 'weiss', 'label' => 'Ohne Hindernisse', 'title' => 'Mit Rollator, Rollstuhl oder Kinderwagen', 'text' => 'Ein neuer Lift erschliesst beide Geschosse. Räume und Anlagen werden möglichst hindernisfrei nach SIA-Norm 500 gestaltet. WC und rollstuhlgerechtes WC mit Wickeltisch im Erdgeschoss.'],
                            ['tone' => 'weiss', 'label' => 'Kühler Ort', 'title' => 'Klimatisiert', 'text' => 'Laden und Café sollen klimatisiert werden. Ältere und hitzeempfindliche Menschen finden an heissen Tagen einen angenehm temperierten Ort.'],
                            ['tone' => 'weiss', 'label' => 'Nach klaren Regeln', 'title' => 'Reservieren und zurückgeben', 'text' => 'Ein Reservationssystem und eine verbindliche Hausordnung sorgen dafür, dass die Räume so zurückgegeben werden, wie man sie vorgefunden hat.'],
                        ],
                    ]),
                    self::block('download', [
                        'heading' => 'Einrichtungsplan Erd- und Untergeschoss',
                        'text' => 'Entwurf, Version 4 vom 15. Oktober 2026. Die Visualisierungen sind Stimmungsbilder der Kerngruppe und keine Bauplanung – die definitive Gestaltung folgt mit dem Bauprojekt.',
                        'file' => 'dokumente/einrichtungsplan-eg-ug-entwurf-v4.pdf',
                        'button_label' => 'Plan herunterladen (PDF, 5.8 MB)',
                        'anchor' => 'plaene',
                        'anchor_label' => 'Pläne',
                    ]),
                ],
            ],

            'projekt' => [
                'title' => 'Projekt',
                'kicker' => 'Das Projekt',
                'headline' => 'Ein Treffpunkt',
                'headline_accent' => 'für das ganze Dorf.',
                'lead' => 'In Bettingen fehlt heute ein Dorftreffpunkt – ein Mittelpunkt des Dorflebens, an dem man sich ungezwungen begegnet. An der Hauptstrasse 85, mitten im Dorf und im Eigentum der Gemeinde, soll dieser Ort entstehen.',
                'meta_description' => 'Konzept, Rollen von Gemeinde, Trägerverein und Betreiberin, Projektstand und Zeitplan des Treffpunkts Bettingen.',
                'is_published' => true,
                'show_in_nav' => true,
                'sort' => 2,
                'blocks' => [
                    self::block('columns', [
                        'kicker' => 'Unser Konzept',
                        'heading' => 'Einkaufen, einkehren, begegnen.',
                        'anchor' => 'konzept',
                        'anchor_label' => 'Konzept',
                        'items' => [
                            ['label' => 'Einkaufen', 'tone' => 'senf', 'title' => 'Dorfladen', 'text' => 'Ergänzt die Versorgung im Dorf mit Produkten des täglichen Bedarfs. Lokale und regionale Produkte werden gezielt berücksichtigt.'],
                            ['label' => 'Einkehren', 'tone' => 'rost', 'title' => 'Café', 'text' => 'Das Herzstück: ein offener, heimeliger Treffpunkt für Znüni, Mittag oder einen Kaffee zwischendurch.'],
                            ['label' => 'Begegnen', 'tone' => 'gruen', 'title' => 'Abends für alle', 'text' => 'Nach Betriebsschluss steht die Caféfläche für Kurse, Sitzungen, Treffen und kleinere Veranstaltungen zur Verfügung – reservierbar über einen digitalen Kalender.'],
                            ['label' => 'Spielen und treffen', 'tone' => 'dunkel', 'title' => 'Untergeschoss', 'text' => 'Raum für Familien und Kinder, für Gruppen und Kurse – und am Samstagabend ein betreuter Jugendtreff.'],
                            ['label' => 'Für alle offen', 'tone' => 'beige', 'title' => 'Jung und Alt', 'text' => 'Für Familien, Kinder, Jugendliche, Erwachsene und ältere Menschen ebenso wie für Vereine und Gruppen.'],
                            ['label' => 'Ohne Hindernisse', 'tone' => 'weiss', 'title' => 'Nach SIA-Norm 500', 'text' => 'Alle sollen den Treffpunkt selbständig nutzen können, ob mit Rollator, Rollstuhl oder Kinderwagen.'],
                        ],
                    ]),
                    self::block('text', [
                        'heading' => 'Mehr als ein Dorfladen.',
                        'body' => '<p>Ein reiner Dorfladen, getragen von einzelnen Betreibern, hat es in einem kleinen Dorf schwer. Unser Konzept setzt deshalb breiter an. Laden und Café bilden die sichtbare, täglich nutzbare Basis. Dazu kommen Begegnungs-, Kurs- und Veranstaltungsangebote, die aus der Bevölkerung selbst entstehen.</p><blockquote>Der Laden bringt Leute ins Haus, das Café hält sie dort, und der Verein macht aus dem Haus einen Ort, der dem ganzen Dorf gehört.</blockquote>',
                        'background' => 'beige',
                    ]),
                    self::block('columns', [
                        'kicker' => 'Klare Rollen, breit abgestützt',
                        'heading' => 'Wer was trägt.',
                        'anchor' => 'rollen',
                        'anchor_label' => 'Rollen',
                        'items' => [
                            ['label' => 'Eigentümerin', 'tone' => 'beige', 'title' => 'Gemeinde', 'text' => 'Bleibt Eigentümerin der Liegenschaft und vermietet die Räume an den Trägerverein. Sie muss den Betrieb weder führen noch finanzieren.'],
                            ['label' => 'Mieter', 'tone' => 'rost', 'title' => 'Trägerverein', 'text' => 'Betreibt den Treffpunkt, ist für Nutzung und Zustand verantwortlich und organisiert die Reservationen für Vereine, Gruppen und Bevölkerung.'],
                            ['label' => 'Betriebsauftrag', 'tone' => 'gruen', 'title' => 'Betreiberin (GmbH)', 'text' => 'Führt Dorfladen und Café professionell und wirtschaftlich verantwortet. Sie finanziert die Einrichtung – getrennt vom Sanierungskredit der Gemeinde.'],
                        ],
                    ]),
                    self::block('text', [
                        'heading' => 'Weshalb ein Verein – und weshalb eine GmbH.',
                        'body' => '<h3>Der Verein</h3><ul><li><strong>Verankerung:</strong> Der Verein stützt das Projekt in der Bevölkerung ab und trägt es über die Aufbauphase hinaus.</li><li><strong>Fördergelder:</strong> Gesuche an Lotteriefonds und Stiftungen setzen eine klar geregelte Trägerschaft voraus.</li><li><strong>Spenden:</strong> Beiträge von Gönnerinnen und Gönnern werden steuerlich abzugsfähig, wenn eine anerkannte Organisation sie bestätigt.</li></ul><h3>Die Betreiber-GmbH</h3><p>Laden und Café brauchen einen professionellen, wirtschaftlich verantworteten Betrieb. So muss der Verein keine unternehmerischen Risiken tragen, und die GmbH muss keine Gemeinwohlaufgaben allein finanzieren. Für die GmbH haben sich bereits Betreiber gefunden, die bereit sind, die Startinvestition zu tragen und mit einem Team aus Angestellten und Freiwilligen den Betrieb sicherzustellen.</p><h3>Idee vor Gewinn</h3><p>Das Konzept ist über Monate ehrenamtlich entstanden. Ziel ist ein lebendiger, dauerhafter Treffpunkt, keine möglichst hohe Rendite. Ein allfälliger Gewinn dient zuerst dazu, die Investition über mehrere Jahre zu amortisieren.</p>',
                    ]),
                    self::block('steps', [
                        'kicker' => 'Projektstand',
                        'heading' => 'Wo wir stehen und wie es weitergeht.',
                        'layout' => 'list',
                        'background' => 'beige',
                        'anchor' => 'stand',
                        'anchor_label' => 'Zeitplan',
                        'items' => $this->milestones(short: false),
                    ]),
                    self::block('text', [
                        'kicker' => '8. Dezember 2026',
                        'heading' => 'Worüber die Gemeindeversammlung entscheidet.',
                        'body' => '<p>Um eine Grundsatzfrage: <strong>Soll das hier vorgestellte Konzept der Kerngruppe weiterverfolgt werden?</strong></p><h3>Bis zum Frühling zu tun</h3><ol><li><strong>Bau und Kosten.</strong> Massnahmen priorisieren, belastbare Offerten einholen, Hindernisfreiheit einplanen. Allfällige Mehrkosten kommen im Frühling als Nachtragskredit vor die Versammlung.</li><li><strong>Verträge.</strong> Mietvertrag zwischen Gemeinde und Trägerverein, Betriebsauftrag zwischen Trägerverein und Betreiberin von Laden und Café.</li><li><strong>Trägerschaft.</strong> Trägerverein aufbauen, Betreiber-GmbH gründen und Finanzierung sichern.</li><li><strong>Partner.</strong> Gespräche mit den örtlichen Vereinen und den bestehenden Anbietern im Dorf, damit das Angebot ergänzt statt konkurriert.</li></ol><h3>Erste Kostenschätzung Sanierung und Umbau: CHF 1,25 Millionen</h3><p>Dies sind bestmögliche Planwerte, keine finalisierten Kosten. Wir setzen alles daran, die Differenz zum bestehenden Kredit so gering wie möglich zu halten und die Gemeindekasse nicht zusätzlich zu belasten.</p>',
                    ]),
                    self::block('steps', [
                        'kicker' => 'Partizipation',
                        'heading' => 'So ist das Konzept entstanden.',
                        'layout' => 'list',
                        'anchor' => 'partizipation',
                        'anchor_label' => 'Partizipation',
                        'items' => [
                            ['date' => '7.2.2026', 'title' => 'Ideenwerkstatt', 'text' => 'Die ganze Bevölkerung ist eingeladen.', 'state' => 'done'],
                            ['date' => '15.4.2026', 'title' => 'Fokus-Workshop', 'text' => 'Erster Prototyp: Raumentwicklung, Betriebskonzept, Gründung einer Kerngruppe.', 'state' => 'done'],
                            ['date' => '27.5.2026', 'title' => 'Kerngruppe', 'text' => 'Drei Szenarien für das Betriebskonzept stehen zur Diskussion. Entscheid: Konzept 3 weiterentwickeln.', 'state' => 'done'],
                            ['date' => '25.6.2026', 'title' => 'Review Szenario 3', 'text' => 'Entscheid: zwei weitere Konzepte ausarbeiten.', 'state' => 'done'],
                            ['date' => '15.8.2026', 'title' => 'Review weiterer zwei Konzepte', 'text' => 'Entscheid: Drei Personen arbeiten einen Kompromissvorschlag aus.', 'state' => 'done'],
                            ['date' => '2.9.2026', 'title' => 'Review Kompromisslösung', 'text' => 'Entscheid: Der Kompromiss wird weiterentwickelt.', 'state' => 'done'],
                            ['date' => '9.9.2026', 'title' => 'Austausch mit dem Gemeinderat', 'text' => 'Präsentation des Kompromissvorschlags bei zwei Vertretern des Gemeinderats.', 'state' => 'done'],
                            ['date' => '7.10.2026', 'title' => 'Austausch mit dem Architekten', 'text' => 'Planung der Infoveranstaltung.', 'state' => 'done'],
                            ['date' => '2.11.2026', 'title' => 'Gründung des Trägervereins', 'text' => 'Detailplanung der Infoveranstaltung.', 'state' => 'done'],
                            ['date' => '14.11.2026', 'title' => 'Infoveranstaltung', 'text' => 'Öffentliche Vorstellung in der Baslerhof-Scheune.', 'state' => 'done'],
                        ],
                    ]),
                    self::block('download', [
                        'heading' => 'Beilage zur Einwohnergemeindeversammlung',
                        'text' => 'Die vierseitige Zusammenfassung des Projekts zur Versammlung vom 8. Dezember 2026 (Entwurf vom 10. November 2026).',
                        'file' => 'dokumente/beilage-egv-8-dezember-2026-entwurf.pdf',
                        'button_label' => 'Beilage herunterladen (PDF, 4.3 MB)',
                    ]),
                ],
            ],

            'verein' => [
                'title' => 'Verein',
                'kicker' => 'Der Verein',
                'headline' => 'Wir schaffen Raum',
                'headline_accent' => 'für eure Ideen.',
                'lead' => 'Der Verein Treffpunkt Bettingen ist aus dem Partizipationsprozess entstanden und wurde am 2. November 2026 gegründet. Er fördert das soziale und kulturelle Leben der Gemeinde und trägt den Treffpunkt an der Hauptstrasse 85.',
                'meta_description' => 'Zweck, Organisation und Mitgliedschaft des Vereins Treffpunkt Bettingen.',
                'is_published' => true,
                'show_in_nav' => true,
                'sort' => 3,
                'blocks' => [
                    self::block('columns', [
                        'kicker' => 'Zweck und Ziele',
                        'heading' => 'Was wir tun.',
                        'anchor' => 'zweck',
                        'anchor_label' => 'Zweck',
                        'items' => [
                            ['tone' => 'rost', 'title' => 'Leben im Dorf fördern', 'text' => 'Wir fördern das soziale und kulturelle Leben in Bettingen.'],
                            ['tone' => 'senf', 'title' => 'Räume für Begegnung', 'text' => 'Wir stellen Räume für generationenübergreifende Begegnungen zur Verfügung.'],
                            ['tone' => 'gruen', 'title' => 'Ideen aus der Bevölkerung', 'text' => 'Wir organisieren keine eigenen Events – die Aktivitäten kommen aus der Bevölkerung.'],
                            ['tone' => 'dunkel', 'title' => 'Infrastruktur und Buchung', 'text' => 'Wir kümmern uns um Infrastruktur, Sauberkeit und Raumbuchung.'],
                            ['tone' => 'beige', 'title' => 'Zusammenhalt', 'text' => 'Wir stärken den gesellschaftlichen Zusammenhalt und die Dorfidentität.'],
                        ],
                    ]),
                    self::block('text', [
                        'kicker' => 'Organisation',
                        'heading' => 'Wie der Verein funktioniert.',
                        'background' => 'beige',
                        'anchor' => 'organisation',
                        'anchor_label' => 'Organisation',
                        'body' => '<h3>Vorstand</h3><p>Co-Präsidium: Gabriella Ess Dahinden und Frank Behner. Der Vorstand besteht aus mindestens drei Mitgliedern, wird für ein Jahr gewählt und vertritt den Verein nach aussen.</p><h3>Generalversammlung</h3><p>Das oberste Organ des Vereins. Sie findet jährlich statt, spätestens am 31. Juli. Stimmberechtigt sind die anwesenden Mitglieder ab 16 Jahren.</p><h3>Rechnungsrevision</h3><p>Zwei Revisorinnen oder Revisoren prüfen jährlich die Kasse und berichten der Generalversammlung.</p><h3>Finanzierung</h3><p>Der Verein finanziert sich über Mitgliederbeiträge, Spenden und Fördergelder. Er kann für die Nutzung der Räume und die Durchführung von Anlässen Regeln erlassen.</p>',
                    ]),
                    self::block('membership', [
                        'kicker' => 'Mitgliedschaft',
                        'heading' => 'Dabei sein.',
                        'anchor' => 'mitgliedschaft',
                        'anchor_label' => 'Mitgliedschaft',
                        'body' => '<p>Mitglied werden kann jede Person ab 12 Jahren, dazu Paare, Familien sowie Vereine und Institutionen.</p><ul><li>Räume des Treffpunkts zu vergünstigten Tarifen nutzen</li><li>Vorrang vor Nichtmitgliedern bei der Reservation</li><li>Stimmrecht an der Generalversammlung ab 16 Jahren</li></ul><p>Je nach Bedarf ist eine aktive Mithilfe erwünscht. Über die Aufnahme entscheidet der Vorstand.</p>',
                        'box_heading' => 'Mitglied werden',
                        'box_text' => 'Das Beitrittsgesuch füllt ihr online in wenigen Minuten aus.',
                        'button_label' => 'Beitrittsgesuch ausfüllen',
                    ]),
                    self::block('download', [
                        'heading' => 'Statuten',
                        'text' => 'Die Statuten des Vereins Treffpunkt Bettingen vom 2. November 2026.',
                        'file' => 'dokumente/statuten-treffpunkt-bettingen.pdf',
                        'button_label' => 'Statuten herunterladen (PDF)',
                        'anchor' => 'statuten',
                        'anchor_label' => 'Statuten',
                    ]),
                ],
            ],

            'impressum' => [
                'title' => 'Impressum',
                'is_published' => true,
                'show_in_footer' => true,
                'sort' => 90,
                'body' => '<h2>Verantwortlich für den Inhalt</h2><p>Verein Treffpunkt Bettingen<br>c/o [NAME, STRASSE NR.]<br>4126 Bettingen<br>Schweiz</p><p>E-Mail: <a href="mailto:info@treffpunkt-bettingen.ch">info@treffpunkt-bettingen.ch</a></p><p>Vertreten durch das Co-Präsidium: Gabriella Ess Dahinden und Frank Behner</p><h2>Bilder</h2><p>Die Visualisierungen von Laden, Café und Untergeschoss sind Stimmungsbilder der Kerngruppe und keine Bauplanung.</p>',
            ],

            'datenschutz' => [
                'title' => 'Datenschutz',
                'is_published' => true,
                'show_in_footer' => true,
                'sort' => 91,
                'body' => '<p><em>Entwurf – vom Vorstand vor der Veröffentlichung zu prüfen.</em></p><h2>Verantwortlich</h2><p>Verein Treffpunkt Bettingen, 4126 Bettingen, <a href="mailto:info@treffpunkt-bettingen.ch">info@treffpunkt-bettingen.ch</a>. Diese Erklärung richtet sich nach dem Schweizer Datenschutzgesetz (DSG).</p><h2>Welche Daten wir bearbeiten</h2><p><strong>Beitrittsgesuch:</strong> Name, Vorname, Adresse, E-Mail, Telefon (freiwillig), bei Kindern und Jugendlichen das Geburtsdatum sowie Name und E-Mail einer erziehungsberechtigten Person, bei Paaren und Familien die Namen der weiteren Personen, die gewählte Mitgliedschaft und ein allfälliger Gönnerbeitrag.</p><p><strong>Besuch der Website:</strong> Der Webserver speichert technisch notwendige Angaben (z. B. IP-Adresse, Zeitpunkt, aufgerufene Seite) in Protokolldateien. Wir verwenden keine Analyse- oder Werbedienste und laden keine Inhalte von Drittanbietern; Schriften werden von unserem eigenen Server geladen.</p><h2>Wozu</h2><p>Die Mitgliederdaten verwenden wir nur zur Verfolgung des Vereinszwecks: für die Aufnahme, die Mitgliederverwaltung, den Versand von Rechnungen und Einladungen. Sie werden vertraulich behandelt und nicht an Dritte weitergegeben (Statuten, Art. 21).</p><h2>Wo die Daten liegen</h2><p>Die Website und ihre Datenbank werden bei green.ch in der Schweiz betrieben. Zugriff auf die Mitgliederdaten haben nur Mitglieder des Vorstands.</p><h2>Wie lange</h2><p>Solange die Mitgliedschaft besteht und danach, soweit es gesetzliche Aufbewahrungspflichten verlangen. Nicht bewilligte Gesuche löschen wir spätestens nach [FRIST].</p><h2>Eure Rechte</h2><p>Ihr könnt jederzeit Auskunft über eure Daten verlangen, sie berichtigen oder löschen lassen und eure Einwilligung widerrufen. Schreibt dazu an <a href="mailto:info@treffpunkt-bettingen.ch">info@treffpunkt-bettingen.ch</a>.</p><h2>Cookies</h2><p>Die Website setzt nur technisch notwendige Cookies (Sitzung und Schutz von Formularen).</p>',
            ],
        ];
    }

    /** Projektstand laut Beilage zur EGV. */
    private function milestones(bool $short): array
    {
        $steps = [
            ['Dez. 2025', 'Kredit von CHF 930\'000 bewilligt', 'Die Gemeindeversammlung bewilligt den Kredit für die Teilsanierung der Liegenschaft Hauptstrasse 85 und die Aufwertung ihres Aussenraums. Über die künftige Nutzung wird bewusst noch nicht entschieden.', 'done'],
            ['2026', 'Partizipation und Kerngruppe', 'Zu einem Partizipationsworkshop ist die ganze Bevölkerung eingeladen. Eine Kerngruppe erarbeitet ehrenamtlich das Nutzungs- und Betriebskonzept.', 'done'],
            ['9.9.2026', 'Gemeinderat', 'Die Kerngruppe stellt das Konzept vor. Der Gemeinderat legt es der Gemeindeversammlung zum Grundsatzentscheid vor.', 'done'],
            ['14.11.2026', 'Infoveranstaltung', 'Öffentliche Vorstellung des Konzepts in der Baslerhof-Scheune.', 'done'],
            ['8.12.2026', 'Gemeindeversammlung: Grundsatzentscheid', 'Soll das Konzept weiterverfolgt werden?', 'current'],
            ['bis April 2027', 'Gründungen, Kosten, Verträge', 'Trägerverein und Betreiber-GmbH werden gegründet, die Kosten abschliessend geklärt und die Verträge ausgearbeitet.', 'planned'],
            ['Frühling 2027', 'Definitiver Entscheid', 'Gemeindeversammlung mit den finalen Kosten, allenfalls mit einem Nachtragskredit.', 'planned'],
            ['Danach', 'Baueingabe, Umbau und Eröffnung', '', 'planned'],
        ];

        if ($short) {
            // Für die Startseite: ohne Gemeinderat-Schritt, kurze Titel.
            unset($steps[2]);
        }

        return array_values(array_map(fn (array $s) => [
            'date' => $s[0],
            'title' => $s[1],
            'text' => $short ? '' : $s[2],
            'state' => $s[3],
        ], $steps));
    }

    private function purposeList(): string
    {
        return '<ul><li>Wir fördern das soziale und kulturelle Leben in Bettingen.</li><li>Wir stellen Räume für generationenübergreifende Begegnungen zur Verfügung.</li><li>Wir organisieren keine eigenen Events – die Aktivitäten kommen aus der Bevölkerung.</li><li>Wir kümmern uns um Infrastruktur, Sauberkeit und Raumbuchung.</li><li>Wir stärken den Zusammenhalt und die Dorfidentität.</li></ul><p><a href="/verein">Mehr über den Verein</a></p>';
    }
}
