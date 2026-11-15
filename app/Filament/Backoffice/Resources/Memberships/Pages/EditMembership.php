<?php

namespace App\Filament\Backoffice\Resources\Memberships\Pages;

use App\Filament\Backoffice\Resources\Memberships\MembershipResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMembership extends EditRecord
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
