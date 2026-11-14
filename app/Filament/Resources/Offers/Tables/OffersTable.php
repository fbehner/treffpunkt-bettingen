<?php

namespace App\Filament\Resources\Offers\Tables;

use App\Models\Offer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('color')
                    ->label('Farbe')
                    ->formatStateUsing(fn (string $state) => Offer::COLORS[$state] ?? $state)
                    ->badge(),
                ToggleColumn::make('is_visible')
                    ->label('Sichtbar'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
