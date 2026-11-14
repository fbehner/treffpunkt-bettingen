<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                Section::make('Inhalt')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                                if ($operation === 'create' && blank($get('slug'))) {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Adresse')
                            ->prefix(fn () => rtrim(config('app.url'), '/').'/')
                            ->helperText('Nur Kleinbuchstaben, Zahlen und Bindestriche.')
                            ->required()
                            ->maxLength(80)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->notIn(Page::RESERVED_SLUGS)
                            ->unique(ignoreRecord: true),
                        RichEditor::make('body')
                            ->label('Text')
                            ->toolbarButtons([
                                ['bold', 'italic', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList', 'blockquote'],
                                ['undo', 'redo'],
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Veröffentlichung')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Veröffentlicht')
                            ->helperText('Nur veröffentlichte Seiten sind öffentlich sichtbar.'),
                        Toggle::make('show_in_nav')
                            ->label('Im Hauptmenü zeigen'),
                        Toggle::make('show_in_footer')
                            ->label('In der Fusszeile zeigen')
                            ->helperText('Z. B. für Impressum und Datenschutz.'),
                        TextInput::make('sort')
                            ->label('Reihenfolge im Menü')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }
}
