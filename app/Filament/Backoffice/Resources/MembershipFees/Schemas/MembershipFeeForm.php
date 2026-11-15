<?php

namespace App\Filament\Backoffice\Resources\MembershipFees\Schemas;

use App\Filament\Backoffice\Forms\SwissDate;
use App\Models\Membership;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MembershipFeeForm
{
    public static function configure(Schema $schema, bool $withMembership = true, ?int $defaultAmount = null): Schema
    {
        return $schema
            ->columns(2)
            ->components(array_values(array_filter([
                $withMembership ? Select::make('membership_id')
                    ->label('Mitgliedschaft')
                    ->relationship('membership', 'number')
                    ->getOptionLabelFromRecordUsing(fn (Membership $record) => 'Nr. '.$record->number.' – '.$record->displayName())
                    ->searchable(['number', 'organisation', 'email'])
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (?string $state, Set $set) => $state ? $set('amount', Membership::find($state)?->fee) : null)
                    ->columnSpanFull() : null,
                TextInput::make('year')
                    ->label('Jahr')
                    ->numeric()
                    ->minValue(2026)
                    ->maxValue(2100)
                    ->default((int) now()->year + (now()->month >= 11 ? 1 : 0))
                    ->required(),
                TextInput::make('amount')
                    ->label('Mitgliederbeitrag')
                    ->prefix('CHF')
                    ->numeric()
                    ->default($defaultAmount)
                    ->required(),
                TextInput::make('donation')
                    ->label('Gönnerbeitrag')
                    ->prefix('CHF')
                    ->numeric(),
                SwissDate::make('paid_at')
                    ->label('Bezahlt am')
                    ->helperText('Leer = offen.'),
                TextInput::make('notes')
                    ->label('Bemerkung')
                    ->columnSpanFull(),
            ])));
    }
}
