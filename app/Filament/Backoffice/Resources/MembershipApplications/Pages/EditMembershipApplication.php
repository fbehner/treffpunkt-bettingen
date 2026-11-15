<?php

namespace App\Filament\Backoffice\Resources\MembershipApplications\Pages;

use App\Filament\Backoffice\Actions\AcceptApplicationAction;
use App\Filament\Backoffice\Actions\DuplicateApplicationAction;
use App\Filament\Backoffice\Resources\MembershipApplications\MembershipApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMembershipApplication extends EditRecord
{
    protected static string $resource = MembershipApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AcceptApplicationAction::make(),
            DuplicateApplicationAction::make(),
            AcceptApplicationAction::openMembership(),
            DeleteAction::make(),
        ];
    }
}
