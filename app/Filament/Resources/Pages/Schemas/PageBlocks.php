<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

/**
 * Bausteine für Seiten. Jeder Baustein hat eine Vorlage in
 * resources/views/blocks/<typ>.blade.php.
 */
class PageBlocks
{
    /** Farben aus dem Plakat für Flächen. */
    public const TONES = [
        'rost' => 'Rostrot',
        'senf' => 'Senfgelb',
        'gruen' => 'Dunkelgrün',
        'dunkel' => 'Dunkelbraun',
        'beige' => 'Hellbeige',
        'weiss' => 'Weiss',
    ];

    public const BACKGROUNDS = [
        'creme' => 'Creme (Standard)',
        'beige' => 'Hellbeige',
        'dunkel' => 'Dunkelbraun',
    ];

    public static function make(): Builder
    {
        return Builder::make('blocks')
            ->label('Bausteine')
            ->addActionLabel('Baustein hinzufügen')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blockNumbers(false)
            ->blockPickerColumns(2)
            ->blocks([
                self::hero(),
                self::text(),
                self::media(),
                self::cards(),
                self::columns(),
                self::facts(),
                self::steps(),
                self::offers(),
                self::membership(),
                self::callout(),
                self::download(),
            ]);
    }

    /** Titel im eingeklappten Zustand: Art + Überschrift. */
    private static function blockLabel(string $kind, ?array $state): string
    {
        $title = $state['heading'] ?? $state['headline'] ?? null;

        return filled($title) ? $kind.': '.$title : $kind;
    }

    /** Gemeinsame Felder: Überschrift, kleine Überschrift, Sprungmarke. */
    private static function headingFields(bool $required = false): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('kicker')
                    ->label('Kleine Überschrift')
                    ->maxLength(80),
                TextInput::make('heading')
                    ->label('Überschrift')
                    ->required($required)
                    ->maxLength(140),
            ]),
        ];
    }

    private static function anchorFields(): Section
    {
        return Section::make('Sprungmarke')
            ->description('Optional: erscheint in der Leiste „Auf dieser Seite“ und ist direkt verlinkbar (z. B. /das-haus#ug).')
            ->collapsed()
            ->compact()
            ->columns(2)
            ->schema([
                TextInput::make('anchor')
                    ->label('Kennung')
                    ->regex('/^[a-z0-9-]+$/')
                    ->helperText('Kleinbuchstaben, Zahlen, Bindestriche'),
                TextInput::make('anchor_label')
                    ->label('Beschriftung in der Leiste'),
            ]);
    }

    private static function image(string $name = 'image', string $label = 'Bild'): array
    {
        return [
            FileUpload::make($name)
                ->label($label)
                ->image()
                ->disk('public')
                ->directory('bilder')
                ->visibility('public')
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth('1800')
                ->maxSize(8192),
            TextInput::make($name.'_alt')
                ->label('Bildbeschreibung')
                ->helperText('Für Menschen, die das Bild nicht sehen (Screenreader).'),
        ];
    }

    private static function background(): Select
    {
        return Select::make('background')
            ->label('Hintergrund')
            ->options(self::BACKGROUNDS)
            ->default('creme')
            ->native(false);
    }

    private static function hero(): Block
    {
        return Block::make('hero')
            ->label(fn (?array $state) => self::blockLabel('Titelbereich mit Bild', $state))
            ->icon(Heroicon::OutlinedSparkles)
            ->schema([
                TextInput::make('kicker')->label('Kleine Überschrift')->maxLength(80),
                Grid::make(2)->schema([
                    TextInput::make('headline')->label('Titel – dunkler Teil')->maxLength(60),
                    TextInput::make('headline_accent')->label('Titel – roter Teil')->maxLength(60),
                ]),
                Textarea::make('lead')->label('Einleitung')->rows(3)->maxLength(400),
                ...self::image(),
                TextInput::make('image_caption')->label('Bildhinweis')->default('Stimmungsbild – keine Bauplanung'),
                Grid::make(2)->schema([
                    TextInput::make('primary_label')->label('Knopf 1 – Text'),
                    TextInput::make('primary_url')->label('Knopf 1 – Link')->helperText('z. B. /das-haus'),
                    TextInput::make('secondary_label')->label('Knopf 2 – Text'),
                    TextInput::make('secondary_url')->label('Knopf 2 – Link'),
                ]),
            ]);
    }

    private static function text(): Block
    {
        return Block::make('text')
            ->label(fn (?array $state) => self::blockLabel('Text', $state))
            ->icon(Heroicon::OutlinedBars3BottomLeft)
            ->schema([
                ...self::headingFields(),
                RichEditor::make('body')
                    ->label('Text')
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'],
                        ['h3'],
                        ['bulletList', 'orderedList', 'blockquote'],
                        ['undo', 'redo'],
                    ]),
                self::background(),
                self::anchorFields(),
            ]);
    }

    private static function media(): Block
    {
        return Block::make('media')
            ->label(fn (?array $state) => self::blockLabel('Bild mit Text', $state))
            ->icon(Heroicon::OutlinedPhoto)
            ->schema([
                ...self::headingFields(true),
                RichEditor::make('body')
                    ->label('Text')
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ]),
                ...self::image(),
                Select::make('image_side')
                    ->label('Bild')
                    ->options(['left' => 'links', 'right' => 'rechts'])
                    ->default('left')
                    ->native(false),
                self::background(),
                self::anchorFields(),
            ]);
    }

    private static function cards(): Block
    {
        return Block::make('cards')
            ->label(fn (?array $state) => self::blockLabel('Karten mit Bild', $state))
            ->icon(Heroicon::OutlinedRectangleGroup)
            ->schema([
                ...self::headingFields(),
                Textarea::make('intro')->label('Einleitung')->rows(2),
                Repeater::make('items')
                    ->label('Karten')
                    ->addActionLabel('Karte hinzufügen')
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)
                    ->schema([
                        TextInput::make('title')->label('Titel')->required(),
                        Textarea::make('text')->label('Text')->rows(3),
                        ...self::image(),
                        TextInput::make('link_label')->label('Link – Text'),
                        TextInput::make('link_url')->label('Link – Adresse'),
                    ])
                    ->defaultItems(2),
                self::background(),
                self::anchorFields(),
            ]);
    }

    private static function columns(): Block
    {
        return Block::make('columns')
            ->label(fn (?array $state) => self::blockLabel('Farbige Felder', $state))
            ->icon(Heroicon::OutlinedViewColumns)
            ->schema([
                ...self::headingFields(),
                Repeater::make('items')
                    ->label('Felder')
                    ->addActionLabel('Feld hinzufügen')
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('label')->label('Kleine Überschrift'),
                            Select::make('tone')->label('Farbe')->options(self::TONES)->default('senf')->native(false),
                        ]),
                        TextInput::make('title')->label('Titel')->required(),
                        Textarea::make('text')->label('Text')->rows(3),
                    ])
                    ->defaultItems(3),
                self::anchorFields(),
            ]);
    }

    private static function facts(): Block
    {
        return Block::make('facts')
            ->label(fn (?array $state) => self::blockLabel('Zahlen & Fakten', $state))
            ->icon(Heroicon::OutlinedChartBar)
            ->schema([
                ...self::headingFields(),
                Repeater::make('items')
                    ->label('Fakten')
                    ->addActionLabel('Fakt hinzufügen')
                    ->grid(2)
                    ->schema([
                        TextInput::make('value')->label('Zahl / Wert')->required()->maxLength(20),
                        TextInput::make('label')->label('Beschriftung')->required(),
                        Select::make('tone')->label('Farbe')->options(self::TONES)->default('senf')->native(false),
                    ])
                    ->defaultItems(4),
                TextInput::make('note')->label('Hinweis darunter'),
            ]);
    }

    private static function steps(): Block
    {
        return Block::make('steps')
            ->label(fn (?array $state) => self::blockLabel('Zeitstrahl', $state))
            ->icon(Heroicon::OutlinedClock)
            ->schema([
                ...self::headingFields(),
                Select::make('layout')
                    ->label('Darstellung')
                    ->options(['row' => 'Waagrecht (kurz)', 'list' => 'Senkrecht (ausführlich)'])
                    ->default('row')
                    ->native(false),
                Repeater::make('items')
                    ->label('Schritte')
                    ->addActionLabel('Schritt hinzufügen')
                    ->collapsible()
                    ->itemLabel(fn (array $state) => trim(($state['date'] ?? '').' '.($state['title'] ?? '')))
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('date')->label('Datum')->required()->maxLength(30),
                            TextInput::make('title')->label('Titel')->required()->columnSpan(2),
                        ]),
                        Textarea::make('text')->label('Beschreibung')->rows(2),
                        Select::make('state')
                            ->label('Stand')
                            ->options(['done' => 'erledigt', 'current' => 'als Nächstes', 'planned' => 'geplant'])
                            ->default('done')
                            ->native(false),
                    ]),
                Grid::make(2)->schema([
                    TextInput::make('link_label')->label('Link darunter – Text'),
                    TextInput::make('link_url')->label('Link darunter – Adresse'),
                ]),
                self::background(),
                self::anchorFields(),
            ]);
    }

    private static function offers(): Block
    {
        return Block::make('offers')
            ->label(fn (?array $state) => self::blockLabel('Angebots-Kacheln', $state))
            ->icon(Heroicon::OutlinedSquares2x2)
            ->schema([
                ...self::headingFields(),
                Textarea::make('intro')->label('Einleitung')->rows(2)
                    ->helperText('Die Kacheln selbst pflegt ihr unter „Angebote“.'),
                Grid::make(2)->schema([
                    TextInput::make('closing_dark')->label('Schlusssatz – dunkler Teil')->default('Auch für eure'),
                    TextInput::make('closing_accent')->label('Schlusssatz – roter Teil')->default('Ideen.'),
                    TextInput::make('primary_label')->label('Knopf 1 – Text'),
                    TextInput::make('primary_url')->label('Knopf 1 – Link'),
                    TextInput::make('secondary_label')->label('Knopf 2 – Text'),
                    TextInput::make('secondary_url')->label('Knopf 2 – Link'),
                ]),
                self::anchorFields(),
            ]);
    }

    private static function membership(): Block
    {
        return Block::make('membership')
            ->label(fn (?array $state) => self::blockLabel('Verein & Mitgliedschaft', $state))
            ->icon(Heroicon::OutlinedUserGroup)
            ->schema([
                ...self::headingFields(),
                RichEditor::make('body')
                    ->label('Text links')
                    ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList'], ['undo', 'redo']]),
                TextInput::make('box_heading')->label('Kasten – Titel')->default('Mitglied werden')
                    ->helperText('Die Beiträge im Kasten kommen aus „Einstellungen“.'),
                Textarea::make('box_text')->label('Kasten – Text')->rows(2),
                TextInput::make('button_label')->label('Knopf – Text')->default('Beitrittsgesuch ausfüllen'),
                self::anchorFields(),
            ]);
    }

    private static function callout(): Block
    {
        return Block::make('callout')
            ->label(fn (?array $state) => self::blockLabel('Hinweis mit Knopf', $state))
            ->icon(Heroicon::OutlinedMegaphone)
            ->schema([
                TextInput::make('heading')->label('Titel')->required(),
                Textarea::make('text')->label('Text')->rows(2),
                Grid::make(3)->schema([
                    TextInput::make('button_label')->label('Knopf – Text'),
                    TextInput::make('button_url')->label('Knopf – Link'),
                    Select::make('tone')->label('Farbe')->options(self::TONES)->default('rost')->native(false),
                ]),
                self::anchorFields(),
            ]);
    }

    private static function download(): Block
    {
        return Block::make('download')
            ->label(fn (?array $state) => self::blockLabel('Download', $state))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->schema([
                TextInput::make('heading')->label('Titel')->required(),
                Textarea::make('text')->label('Beschreibung')->rows(2),
                FileUpload::make('file')
                    ->label('Datei (PDF)')
                    ->disk('public')
                    ->directory('dokumente')
                    ->visibility('public')
                    ->acceptedFileTypes(['application/pdf'])
                    ->preserveFilenames()
                    ->maxSize(20480)
                    ->required(),
                TextInput::make('button_label')->label('Knopf – Text')->default('Herunterladen (PDF)'),
                self::anchorFields(),
            ]);
    }
}
