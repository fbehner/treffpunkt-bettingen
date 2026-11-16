<?php

namespace App\Filament\Backoffice\Resources\RoomReservations\Pages;

use App\Filament\Backoffice\Actions\ReservationActions;
use App\Filament\Backoffice\Resources\RoomReservations\RoomReservationResource;
use App\Filament\Backoffice\Resources\RoomReservations\Schemas\RoomReservationForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRoomReservation extends EditRecord
{
    protected static string $resource = RoomReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ReservationActions::confirm()->after(fn () => $this->refreshFormData(['status', 'decision_note'])),
            ReservationActions::decline()->after(fn () => $this->refreshFormData(['status', 'decision_note'])),
            ReservationActions::cancel()->after(fn () => $this->refreshFormData(['status', 'decision_note'])),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return RoomReservationForm::fromModel($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return RoomReservationForm::toModel($data);
    }
}
