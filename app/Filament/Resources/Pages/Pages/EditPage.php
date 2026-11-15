<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Die Startseite darf nicht gelöscht werden.
            DeleteAction::make()
                ->hidden(fn () => $this->record->isHome()),
        ];
    }
}
