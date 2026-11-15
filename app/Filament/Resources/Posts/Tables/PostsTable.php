<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->weight('bold')
                    ->limit(60),
                TextColumn::make('kind')
                    ->label('Art')
                    ->formatStateUsing(fn (string $state) => Post::KINDS[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => $state === 'event' ? 'primary' : 'gray'),
                TextColumn::make('event_starts_at')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('–'),
                TextColumn::make('published_at')
                    ->label('Veröffentlicht ab')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                ToggleColumn::make('is_published')
                    ->label('Sichtbar'),
            ])
            ->filters([
                SelectFilter::make('kind')->label('Art')->options(Post::KINDS),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Ansehen')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Post $record) => route('posts.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (Post $record) => $record->is_published),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
