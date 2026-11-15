<?php

namespace App\Filament\Backoffice\Resources\Memberships;

use App\Filament\Backoffice\Resources\Memberships\Pages\CreateMembership;
use App\Filament\Backoffice\Resources\Memberships\Pages\EditMembership;
use App\Filament\Backoffice\Resources\Memberships\Pages\ListMemberships;
use App\Filament\Backoffice\Resources\Memberships\RelationManagers\FeesRelationManager;
use App\Filament\Backoffice\Resources\Memberships\Schemas\MembershipForm;
use App\Filament\Backoffice\Resources\Memberships\Tables\MembershipsTable;
use App\Models\Membership;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'Mitgliedschaft';

    protected static ?string $pluralModelLabel = 'Mitglieder';

    protected static string|UnitEnum|null $navigationGroup = 'Mitglieder';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'number';

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageMembers() ?? false;
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record ? 'Nr. '.$record->number.' – '.$record->displayName() : 'Mitgliedschaft';
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return 'Nr. '.$record->number.' – '.$record->displayName();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'organisation', 'email', 'people.first_name', 'people.last_name'];
    }

    public static function form(Schema $schema): Schema
    {
        return MembershipForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembershipsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMemberships::route('/'),
            'create' => CreateMembership::route('/create'),
            'edit' => EditMembership::route('/{record}/edit'),
        ];
    }
}
