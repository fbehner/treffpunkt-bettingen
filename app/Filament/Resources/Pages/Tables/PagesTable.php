<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->label('Adresse')
                    ->prefix('/')
                    ->color('gray'),
                ToggleColumn::make('is_published')
                    ->label('Veröffentlicht'),
                IconColumn::make('show_in_nav')
                    ->label('Menü')
                    ->boolean(),
                IconColumn::make('show_in_footer')
                    ->label('Fusszeile')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Zuletzt geändert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Ansehen')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Page $record) => $record->url())
                    ->openUrlInNewTab()
                    ->visible(fn (Page $record) => $record->is_published),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
