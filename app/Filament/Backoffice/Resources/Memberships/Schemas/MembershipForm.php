<?php

namespace App\Filament\Backoffice\Resources\Memberships\Schemas;

use App\Filament\Backoffice\Forms\SwissDate;
use App\Models\Membership;
use App\Models\MembershipPerson;
use App\Support\MembershipType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MembershipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Mitgliedschaft')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        TextInput::make('number')
                            ->label('Mitgliedernummer')
                            ->numeric()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => Membership::nextNumber()),
                        Select::make('type')
                            ->label('Art')
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
                            ->visible(fn (Get $get) => $get('type') === 'verein')
                            ->required(fn (Get $get) => $get('type') === 'verein'),
                        TextInput::make('street')->label('Strasse und Nr.')->columnSpan(2),
                        TextInput::make('zip_city')->label('PLZ und Ort')->default('4126 Bettingen'),
                        TextInput::make('email')->label('E-Mail (Rechnung, Einladungen)')->email()->columnSpan(2),
                        TextInput::make('phone')->label('Telefon')->tel(),
                    ]),

                Section::make('Status')
                    ->columnSpan(1)
                    ->schema([
                        SwissDate::make('joined_at')
                            ->label('Eintritt')
                            ->default(now()->format('d.m.Y'))
                            ->required(),
                        SwissDate::make('left_at')
                            ->label('Austritt / Ausschluss')
                            ->helperText('Leer = aktives Mitglied.')
                            ->live(onBlur: true),
                        Select::make('exit_reason')
                            ->label('Grund')
                            ->options(Membership::EXIT_REASONS)
                            ->native(false)
                            ->visible(fn (Get $get) => filled($get('left_at')))
                            ->required(fn (Get $get) => filled($get('left_at'))),
                        TextEntry::make('voting')
                            ->label('Stimmberechtigt an der GV')
                            ->state(fn (?Membership $record) => $record ? $record->votingCount().' Person(en)' : '–')
                            ->hiddenOn('create'),
                        TextEntry::make('transition')
                            ->label('Altersübergang')
                            ->state(fn (?Membership $record) => $record?->transitionHint() ? 'Wechsel zu '.$record->transitionHint() : null)
                            ->color('warning')
                            ->visible(fn (?Membership $record) => $record?->transitionHint() !== null),
                        TextEntry::make('application')
                            ->label('Beitrittsgesuch')
                            ->state(fn (?Membership $record) => $record?->application
                                ? 'vom '.$record->application->receivedDate()?->format('d.m.Y')
                                : 'kein verknüpftes Gesuch')
                            ->url(fn (?Membership $record) => $record?->application
                                ? route('filament.backoffice.resources.membership-applications.edit', $record->application)
                                : null)
                            ->hiddenOn('create'),
                    ]),

                Section::make('Personen')
                    ->description('Bei Paaren und Familien alle Personen erfassen – das Stimmrecht an der GV gilt pro Person ab 16 Jahren.')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('people')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort')
                            ->addActionLabel('Person hinzufügen')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state) => trim(($state['first_name'] ?? '').' '.($state['last_name'] ?? '')).(isset($state['relation']) ? ' · '.(MembershipPerson::RELATIONS[$state['relation']] ?? '') : ''))
                            ->schema([
                                Grid::make(4)->schema([
                                    Select::make('relation')
                                        ->label('Rolle')
                                        ->options(MembershipPerson::RELATIONS)
                                        ->default('haupt')
                                        ->required()
                                        ->native(false),
                                    TextInput::make('first_name')->label('Vorname')->required(),
                                    TextInput::make('last_name')->label('Name'),
                                    SwissDate::birthdate(),
                                    TextInput::make('birth_year')
                                        ->label('Jahrgang')
                                        ->helperText('Falls kein Geburtsdatum')
                                        ->numeric()
                                        ->minValue(1900)
                                        ->maxValue((int) date('Y')),
                                    TextInput::make('email')->label('E-Mail')->email(),
                                    TextInput::make('phone')->label('Telefon')->tel(),
                                ]),
                            ]),
                    ]),

                Section::make('Notizen')
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')->hiddenLabel()->rows(4),
                    ]),
            ]);
    }
}
