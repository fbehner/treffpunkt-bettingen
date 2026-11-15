<?php

namespace App\Filament\Backoffice\Resources\Memberships\Pages;

use App\Filament\Backoffice\Resources\Memberships\MembershipResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMembership extends CreateRecord
{
    protected static string $resource = MembershipResource::class;
}
