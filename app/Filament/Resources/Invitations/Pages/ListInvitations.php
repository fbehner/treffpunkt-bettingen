<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListInvitations extends ListRecords
{
    protected static string $resource = InvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Person einladen')->icon(Heroicon::OutlinedEnvelope),
        ];
    }
}
