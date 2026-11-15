<?php

namespace App\Filament\Resources\MembershipApplications;

use App\Filament\Resources\MembershipApplications\Pages\CreateMembershipApplication;
use App\Filament\Resources\MembershipApplications\Pages\EditMembershipApplication;
use App\Filament\Resources\MembershipApplications\Pages\ListMembershipApplications;
use App\Filament\Resources\MembershipApplications\Schemas\MembershipApplicationForm;
use App\Filament\Resources\MembershipApplications\Tables\MembershipApplicationsTable;
use App\Models\MembershipApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MembershipApplicationResource extends Resource
{
    protected static ?string $model = MembershipApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $modelLabel = 'Beitrittsgesuch';

    protected static ?string $pluralModelLabel = 'Beitrittsgesuche';

    protected static string|\UnitEnum|null $navigationGroup = 'Mitglieder';

    protected static ?int $navigationSort = 5;

    /** Personendaten: nur für die Administration (Vorstand). */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $open = MembershipApplication::query()->whereIn('status', ['eingegangen', 'bestaetigung'])->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Offene Gesuche';
    }

    public static function form(Schema $schema): Schema
    {
        return MembershipApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembershipApplicationsTable::configure($table);
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
            'index' => ListMembershipApplications::route('/'),
            'create' => CreateMembershipApplication::route('/create'),
            'edit' => EditMembershipApplication::route('/{record}/edit'),
        ];
    }
}
