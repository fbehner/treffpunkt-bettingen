<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Person einladen')
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(InvitationResource::getUrl('create')),
            CreateAction::make()->label('Konto direkt anlegen')->color('gray'),
        ];
    }
}
