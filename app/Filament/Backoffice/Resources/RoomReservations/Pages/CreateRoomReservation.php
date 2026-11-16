<?php

namespace App\Filament\Backoffice\Resources\RoomReservations\Pages;

use App\Filament\Backoffice\Resources\RoomReservations\RoomReservationResource;
use App\Filament\Backoffice\Resources\RoomReservations\Schemas\RoomReservationForm;
use Filament\Resources\Pages\CreateRecord;

/**
 * Eigene Einträge (Jugendtreff, Telefon, E-Mail) – ohne E-Mail an jemanden.
 */
class CreateRoomReservation extends CreateRecord
{
    protected static string $resource = RoomReservationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = RoomReservationForm::toModel($data);

        if ($data['status'] !== 'angefragt') {
            $data['decided_at'] = now();
        }

        return $data;
    }
}
