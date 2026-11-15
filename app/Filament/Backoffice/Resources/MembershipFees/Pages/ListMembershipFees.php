<?php

namespace App\Filament\Backoffice\Resources\MembershipFees\Pages;

use App\Filament\Backoffice\Resources\MembershipFees\MembershipFeeResource;
use App\Filament\Backoffice\Resources\MembershipFees\Tables\MembershipFeesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMembershipFees extends ListRecords
{
    protected static string $resource = MembershipFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            MembershipFeesTable::generateAction(),
            MembershipFeesTable::exportYearAction(),
            CreateAction::make()->label('Einzeln erfassen')->color('gray'),
        ];
    }
}
