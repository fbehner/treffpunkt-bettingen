<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Models\Invitation;
use App\Models\User;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Person einladen')
                ->description('Die Person bekommt einen Link per E-Mail ('.Invitation::VALID_DAYS.' Tage gültig), legt damit ihr Konto an und wartet dann auf eure Freigabe. Die Rollen weist ihr nach der Registrierung unter „Benutzer“ zu.')
                ->columns(2)
                ->schema(self::fields()),
        ]);
    }

    /** Auch für die Aktion „Person einladen“ in der Benutzerliste. */
    public static function fields(): array
    {
        return [
            TextInput::make('email')
                ->label('E-Mail')
                ->email()
                ->required()
                ->maxLength(160)
                ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                    if (User::query()->where('email', $value)->exists()) {
                        $fail('Für diese Adresse gibt es bereits ein Konto.');
                    }
                    if (Invitation::query()->open()->where('email', $value)->exists()) {
                        $fail('Diese Person ist bereits eingeladen. Ihr könnt die Einladung in der Liste erneut senden.');
                    }
                }),
            TextInput::make('name')
                ->label('Name (optional)')
                ->helperText('Für die Anrede in der E-Mail.')
                ->maxLength(120),
        ];
    }
}
