<?php

namespace App\Filament\Backoffice\Resources\MembershipFees\Pages;

use App\Filament\Backoffice\Resources\MembershipFees\MembershipFeeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMembershipFee extends EditRecord
{
    protected static string $resource = MembershipFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
