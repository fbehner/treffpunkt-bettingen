<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Das eigene Konto kann man nicht selbst löschen.
            DeleteAction::make()
                ->hidden(fn () => $this->record->is(auth()->user())),
        ];
    }
}
