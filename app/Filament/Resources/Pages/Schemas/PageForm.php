<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Tabs::make()
                    ->columnSpan(2)
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Bausteine')
                            ->schema([
                                PageBlocks::make()->hiddenLabel(),
                            ]),
                        Tab::make('Seitenkopf')
                            ->schema([
                                TextInput::make('kicker')
                                    ->label('Kleine Überschrift')
                                    ->maxLength(80),
                                Grid::make(2)->schema([
                                    TextInput::make('headline')
                                        ->label('Grosser Titel – dunkler Teil')
                                        ->helperText('Leer = Seitentitel')
                                        ->maxLength(80),
                                    TextInput::make('headline_accent')
                                        ->label('Grosser Titel – roter Teil')
                                        ->maxLength(80),
                                ]),
                                Textarea::make('lead')
                                    ->label('Einleitung')
                                    ->rows(3)
                                    ->maxLength(500),
                                Text::make('Der Seitenkopf wird nicht angezeigt, wenn der erste Baustein ein „Titelbereich mit Bild“ ist.'),
                            ]),
                        Tab::make('Freitext')
                            ->schema([
                                RichEditor::make('body')
                                    ->label('Text vor den Bausteinen')
                                    ->helperText('Für einfache Seiten wie Impressum oder Datenschutz.')
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'link'],
                                        ['h2', 'h3'],
                                        ['bulletList', 'orderedList', 'blockquote'],
                                        ['undo', 'redo'],
                                    ]),
                            ]),
                    ]),
                Section::make('Seite')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titel (Menü)')
                            ->required()
                            ->maxLength(80)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                                if ($operation === 'create' && blank($get('slug'))) {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Adresse')
                            ->prefix('/')
                            ->helperText(fn (?Page $record) => $record?->isHome()
                                ? 'Das ist die Startseite – Adresse nicht ändern.'
                                : 'Nur Kleinbuchstaben, Zahlen und Bindestriche.')
                            ->required()
                            ->maxLength(80)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->notIn(Page::RESERVED_SLUGS)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (?Page $record) => $record?->isHome() ?? false)
                            ->dehydrated(fn (?Page $record) => ! ($record?->isHome() ?? false)),
                        Toggle::make('is_published')
                            ->label('Veröffentlicht'),
                        Toggle::make('show_in_nav')
                            ->label('Im Hauptmenü'),
                        Toggle::make('show_in_footer')
                            ->label('In der Fusszeile'),
                        TextInput::make('sort')
                            ->label('Reihenfolge im Menü')
                            ->numeric()
                            ->default(0),
                        Textarea::make('meta_description')
                            ->label('Beschreibung für Suchmaschinen')
                            ->rows(3)
                            ->maxLength(300),
                    ]),
            ]);
    }
}
