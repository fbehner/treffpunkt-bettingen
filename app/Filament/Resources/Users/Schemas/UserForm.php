<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Zugang zur Verwaltung')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        CheckboxList::make('roles')
                            ->label('Rollen')
                            ->options(User::ROLES)
                            ->descriptions(User::ROLE_HINTS)
                            ->default(['redaktion'])
                            ->required()
                            ->minItems(1)
                            ->columnSpanFull()
                            // Eigenes Webadmin-Recht nicht versehentlich entfernen (sonst sperrt man sich aus).
                            ->rule(fn (?User $record) => function (string $attribute, mixed $value, \Closure $fail) use ($record) {
                                if ($record?->is(auth()->user()) && $record->isAdmin() && ! in_array('admin', (array) $value, true)) {
                                    $fail('Das eigene Webadmin-Recht kann man nicht selbst entfernen.');
                                }
                            }),
                        Toggle::make('is_active')
                            ->label('Aktiv')
                            ->helperText('Deaktivierte Konten können sich nicht mehr anmelden.')
                            ->default(true)
                            ->inline(false),
                        TextInput::make('password')
                            ->label(fn (string $operation) => $operation === 'create' ? 'Passwort' : 'Neues Passwort')
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leer lassen, um das Passwort nicht zu ändern.' : 'Mindestens 10 Zeichen.')
                            ->password()
                            ->revealable()
                            ->minLength(10)
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
