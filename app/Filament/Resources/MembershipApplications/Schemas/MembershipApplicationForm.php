<?php

namespace App\Filament\Resources\MembershipApplications\Schemas;

use App\Models\MembershipApplication;
use App\Support\MembershipType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MembershipApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Gesuch')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label('Mitgliedschaft')
                            ->options(MembershipType::options())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Set $set) => $state ? $set('fee', MembershipType::from($state)->fee()) : null)
                            ->native(false),
                        TextInput::make('fee')
                            ->label('Jahresbeitrag')
                            ->prefix('CHF')
                            ->numeric()
                            ->required(),
                        TextInput::make('organisation')
                            ->label('Verein / Institution')
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('type') === 'verein'),
                        TextInput::make('first_name')->label('Vorname')->required(),
                        TextInput::make('last_name')->label('Name')->required(),
                        DatePicker::make('birthdate')
                            ->label('Geburtsdatum')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->visible(fn (Get $get) => in_array($get('type'), ['jugend', 'kind'], true)),
                        Grid::make(2)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => in_array($get('type'), ['paar', 'familie'], true))
                            ->schema([
                                TextInput::make('partner_first_name')->label('Vorname, 2. Person'),
                                TextInput::make('partner_last_name')->label('Name, 2. Person'),
                            ]),
                        Textarea::make('children')
                            ->label('Kinder')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('type') === 'familie'),
                        TextInput::make('street')->label('Strasse und Nr.')->required(),
                        TextInput::make('zip_city')->label('PLZ und Ort')->required(),
                        TextInput::make('phone')->label('Telefon')->tel(),
                        TextInput::make('email')->label('E-Mail')->email()->required(),
                        TextInput::make('donation')->label('Gönnerbeitrag')->prefix('CHF')->numeric(),
                        Textarea::make('message')->label('Mitteilung')->rows(3)->columnSpanFull(),
                    ]),
                Section::make('Bearbeitung')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(MembershipApplication::STATUSES)
                            ->default('eingegangen')
                            ->required()
                            ->native(false),
                        Textarea::make('notes')
                            ->label('Interne Notizen')
                            ->helperText('Nur in der Verwaltung sichtbar.')
                            ->rows(5),
                        Section::make('Erziehungsberechtigte Person')
                            ->compact()
                            ->visible(fn (Get $get) => in_array($get('type'), ['jugend', 'kind'], true))
                            ->schema([
                                TextInput::make('guardian_name')->label('Name'),
                                TextInput::make('guardian_email')->label('E-Mail')->email(),
                                DateTimePicker::make('guardian_confirmed_at')
                                    ->label('Bestätigt am')
                                    ->native(false)
                                    ->displayFormat('d.m.Y H:i'),
                            ]),
                        DateTimePicker::make('statutes_accepted_at')
                            ->label('Statuten akzeptiert am')
                            ->native(false)
                            ->displayFormat('d.m.Y H:i'),
                        DateTimePicker::make('created_at')
                            ->label('Eingegangen am')
                            ->native(false)
                            ->displayFormat('d.m.Y H:i')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                    ]),
            ]);
    }
}
