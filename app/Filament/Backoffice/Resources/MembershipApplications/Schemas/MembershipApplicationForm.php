<?php

namespace App\Filament\Backoffice\Resources\MembershipApplications\Schemas;

use App\Filament\Backoffice\Actions\DuplicateApplicationAction;
use App\Filament\Backoffice\Forms\SwissDate;
use App\Models\MembershipApplication;
use App\Support\MembershipType;
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
                DuplicateApplicationAction::callout(),
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
                        SwissDate::birthdate()
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
                        Select::make('source')
                            ->label('Eingegangen per')
                            ->options(MembershipApplication::SOURCES)
                            ->default('papier')
                            ->required()
                            ->native(false),
                        SwissDate::make('received_at')
                            ->label('Eingangsdatum')
                            ->helperText('Bei Online-Gesuchen automatisch.')
                            ->default(now()->format('d.m.Y')),
                        Select::make('status')
                            ->label('Status')
                            ->helperText('„Aufgenommen“ setzt ihr über den Knopf „Aufnehmen“ – so entsteht die Mitgliedschaft.')
                            ->options(fn (?MembershipApplication $record) => $record?->membership_id
                                ? MembershipApplication::STATUSES
                                : collect(MembershipApplication::STATUSES)->except(['aufgenommen'])->all())
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
                                    ->label('Bestätigt / unterschrieben am'),
                            ]),
                        DateTimePicker::make('statutes_accepted_at')
                            ->label('Statuten akzeptiert / unterschrieben am')
                            ->default(now()),
                        DateTimePicker::make('created_at')
                            ->label('Erfasst am')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                    ]),
            ]);
    }
}
