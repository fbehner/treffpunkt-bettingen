<?php

namespace App\Filament\Backoffice\Resources\Memberships\RelationManagers;

use App\Filament\Backoffice\Resources\MembershipFees\Schemas\MembershipFeeForm;
use App\Filament\Backoffice\Resources\MembershipFees\Tables\MembershipFeesTable;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Jahresbeiträge auf der Seite einer Mitgliedschaft.
 */
class FeesRelationManager extends RelationManager
{
    protected static string $relationship = 'fees';

    protected static ?string $title = 'Jahresbeiträge';

    protected static ?string $modelLabel = 'Jahresbeitrag';

    public function form(Schema $schema): Schema
    {
        return MembershipFeeForm::configure($schema, withMembership: false, defaultAmount: $this->getOwnerRecord()->fee);
    }

    public function table(Table $table): Table
    {
        return MembershipFeesTable::configure($table, withMembership: false)
            ->headerActions([
                CreateAction::make()->label('Beitrag erfassen'),
            ])
            ->recordActions([
                MembershipFeesTable::markPaidAction(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
