<?php

namespace App\Filament\Backoffice\Resources\RoomReservations;

use App\Filament\Backoffice\Resources\RoomReservations\Pages\CreateRoomReservation;
use App\Filament\Backoffice\Resources\RoomReservations\Pages\EditRoomReservation;
use App\Filament\Backoffice\Resources\RoomReservations\Pages\ListRoomReservations;
use App\Filament\Backoffice\Resources\RoomReservations\Schemas\RoomReservationForm;
use App\Filament\Backoffice\Resources\RoomReservations\Tables\RoomReservationsTable;
use App\Models\RoomReservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RoomReservationResource extends Resource
{
    protected static ?string $model = RoomReservation::class;

    protected static ?string $slug = 'reservationen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'Reservation';

    protected static ?string $pluralModelLabel = 'Reservationen';

    protected static string|UnitEnum|null $navigationGroup = 'Reservationen';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageReservations() ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $open = RoomReservation::query()->where('status', 'angefragt')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Offene Anfragen';
    }

    public static function form(Schema $schema): Schema
    {
        return RoomReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomReservationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoomReservations::route('/'),
            'create' => CreateRoomReservation::route('/create'),
            'edit' => EditRoomReservation::route('/{record}/edit'),
        ];
    }
}
