<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\Invitations;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** Hatte das Konto vor dem Speichern schon eine Rolle? */
    protected bool $hadRoles = true;

    protected function beforeSave(): void
    {
        $this->hadRoles = ! empty($this->record->getOriginal('roles'));
    }

    protected function afterSave(): void
    {
        if (! $this->hadRoles && ! empty($this->record->roles) && $this->record->is_active) {
            Invitations::notifyAccessGranted($this->record);
            Notification::make()->title('Freigeschaltet – '.$this->record->name.' wurde per E-Mail informiert')->success()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Das eigene Konto kann man nicht selbst löschen.
            DeleteAction::make()
                ->hidden(fn () => $this->record->is(auth()->user())),
        ];
    }
}
