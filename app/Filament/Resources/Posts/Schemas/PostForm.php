<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Beitrag')
                    ->columnSpan(2)
                    ->schema([
                        Select::make('kind')
                            ->label('Art')
                            ->options(Post::KINDS)
                            ->default('news')
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(160)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                                if ($operation === 'create' && blank($get('slug'))) {
                                    $set('slug', Str::slug($state ?? ''));
                                }
                            }),
                        Section::make('Termin')
                            ->compact()
                            ->columns(2)
                            ->visible(fn (Get $get) => $get('kind') === 'event')
                            ->schema([
                                DateTimePicker::make('event_starts_at')
                                    ->label('Datum und Zeit')
                                    ->helperText('Zeit 00:00 = ohne Uhrzeit')
                                    ->native(false)
                                    ->seconds(false)
                                    ->displayFormat('d.m.Y H:i')
                                    ->required(fn (Get $get) => $get('kind') === 'event'),
                                TextInput::make('event_location')
                                    ->label('Ort'),
                            ]),
                        Textarea::make('excerpt')
                            ->label('Kurztext')
                            ->helperText('Erscheint in der Übersicht.')
                            ->rows(3)
                            ->maxLength(400),
                        RichEditor::make('body')
                            ->label('Text')
                            ->toolbarButtons([
                                ['bold', 'italic', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList', 'blockquote'],
                                ['undo', 'redo'],
                            ]),
                        FileUpload::make('image')
                            ->label('Bild')
                            ->image()
                            ->disk('public')
                            ->directory('bilder')
                            ->visibility('public')
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('1800')
                            ->maxSize(8192),
                        TextInput::make('image_alt')
                            ->label('Bildbeschreibung'),
                    ]),
                Section::make('Veröffentlichung')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Veröffentlicht')
                            ->default(true),
                        DateTimePicker::make('published_at')
                            ->label('Datum')
                            ->helperText('Leer = sofort. In der Zukunft = erscheint erst dann.')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d.m.Y H:i')
                            ->default(now()),
                        TextInput::make('slug')
                            ->label('Adresse')
                            ->prefix('/aktuelles/')
                            ->required()
                            ->maxLength(120)
                            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                            ->unique(ignoreRecord: true),
                    ]),
            ]);
    }
}
