<?php

namespace App\Filament\Backoffice\Resources\RoomReservations\Pages;

use App\Filament\Backoffice\Resources\RoomReservations\RoomReservationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoomReservations extends ListRecords
{
    protected static string $resource = RoomReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Reservation erfassen'),
        ];
    }
}
