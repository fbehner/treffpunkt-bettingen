<?php

namespace App\Filament\Backoffice\Resources\MembershipApplications\Pages;

use App\Filament\Backoffice\Resources\MembershipApplications\MembershipApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMembershipApplications extends ListRecords
{
    protected static string $resource = MembershipApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Gesuch erfassen (Papier, E-Mail …)'),
        ];
    }
}
