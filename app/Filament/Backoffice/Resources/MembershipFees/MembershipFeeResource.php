<?php

namespace App\Filament\Backoffice\Resources\MembershipFees;

use App\Filament\Backoffice\Resources\MembershipFees\Pages\CreateMembershipFee;
use App\Filament\Backoffice\Resources\MembershipFees\Pages\EditMembershipFee;
use App\Filament\Backoffice\Resources\MembershipFees\Pages\ListMembershipFees;
use App\Filament\Backoffice\Resources\MembershipFees\Schemas\MembershipFeeForm;
use App\Filament\Backoffice\Resources\MembershipFees\Tables\MembershipFeesTable;
use App\Models\MembershipFee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MembershipFeeResource extends Resource
{
    protected static ?string $model = MembershipFee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'Jahresbeitrag';

    protected static ?string $pluralModelLabel = 'Beiträge';

    protected static string|\UnitEnum|null $navigationGroup = 'Beiträge';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageMembers() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return MembershipFeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembershipFeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembershipFees::route('/'),
            'create' => CreateMembershipFee::route('/create'),
            'edit' => EditMembershipFee::route('/{record}/edit'),
        ];
    }
}
