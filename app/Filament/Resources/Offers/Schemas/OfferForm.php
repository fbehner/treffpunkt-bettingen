<?php

namespace App\Filament\Resources\Offers\Schemas;

use App\Models\Offer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kachel')
                    ->description('Erscheint im Raster „Das alles könnte hier stattfinden“ auf der Startseite.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(60)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Beschreibung')
                            ->helperText('Ein bis zwei kurze Sätze.')
                            ->required()
                            ->rows(3)
                            ->maxLength(200)
                            ->columnSpanFull(),
                        Select::make('color')
                            ->label('Farbe')
                            ->options(Offer::COLORS)
                            ->default('rost')
                            ->required()
                            ->native(false),
                        Select::make('icon')
                            ->label('Symbol')
                            ->options(Offer::ICONS)
                            ->default('book')
                            ->required()
                            ->searchable()
                            ->native(false),
                        TextInput::make('sort')
                            ->label('Reihenfolge')
                            ->helperText('Kleinere Zahl = weiter vorne. In der Liste auch per Ziehen änderbar.')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_visible')
                            ->label('Auf der Website anzeigen')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
