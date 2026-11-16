<?php

namespace App\Filament\Backoffice\Resources\RoomReservations\Tables;

use App\Filament\Backoffice\Actions\ReservationActions;
use App\Models\RoomReservation;
use App\Support\RoomPackage;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoomReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Wann')
                    ->state(fn (RoomReservation $record) => $record->timeLabel())
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('starts_at', $direction === 'asc' ? 'asc' : 'desc'))
                    ->weight('bold'),
                TextColumn::make('package')
                    ->label('Raum')
                    ->formatStateUsing(fn (string $state) => RoomPackage::from($state)->label())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('purpose')
                    ->label('Anlass')
                    ->description(fn (RoomReservation $record) => $record->public_title ? 'Kalender: '.$record->public_title : null)
                    ->searchable()
                    ->limit(40),
                TextColumn::make('last_name')
                    ->label('Wer')
                    ->state(fn (RoomReservation $record) => $record->contactName())
                    ->description(fn (RoomReservation $record) => $record->is_member ? 'Mitglied' : null)
                    ->searchable(['first_name', 'last_name', 'organisation', 'email']),
                TextColumn::make('price')
                    ->label('Preis')
                    ->prefix('CHF ')
                    ->placeholder('–')
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('conflict')
                    ->label('')
                    ->state(fn (RoomReservation $record) => $record->status === 'angefragt' && ($record->confirmedConflicts()->isNotEmpty() || $record->competingRequests()->isNotEmpty()) ? 'Überschneidung' : null)
                    ->badge()
                    ->color('warning')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->tooltip('Gleiche Zeit und gleicher Raum wie eine andere Reservation oder Anfrage'),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state) => RoomReservation::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'angefragt' => 'warning',
                        'bestaetigt' => 'success',
                        'abgelehnt' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('source')
                    ->label('Per')
                    ->formatStateUsing(fn (string $state) => RoomReservation::SOURCES[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('upcoming')
                    ->label('Zeitraum')
                    ->placeholder('Alle')
                    ->trueLabel('Ab heute')
                    ->falseLabel('Vergangene')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $q) => $q->where('ends_at', '>=', now()->startOfDay()),
                        false: fn (Builder $q) => $q->where('ends_at', '<', now()->startOfDay()),
                        blank: fn (Builder $q) => $q,
                    ),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(RoomReservation::STATUSES),
                SelectFilter::make('package')
                    ->label('Raum')
                    ->options(RoomPackage::options()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ReservationActions::confirm(),
                    ReservationActions::decline(),
                    ReservationActions::cancel(),
                ]),
                EditAction::make(),
            ])
            ->emptyStateHeading('Keine Reservationen')
            ->emptyStateDescription('Anfragen von der Website erscheinen hier. Eigene Belegungen (z. B. Jugendtreff) erfasst ihr oben rechts.');
    }
}
