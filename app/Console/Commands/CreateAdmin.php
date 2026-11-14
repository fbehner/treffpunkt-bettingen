<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Legt ein Administrationskonto an – z. B. das erste Konto nach der Installation.
 */
class CreateAdmin extends Command
{
    protected $signature = 'verein:admin';

    protected $description = 'Administrationskonto für die Verwaltung anlegen';

    public function handle(): int
    {
        $name = text('Name', required: true);
        $email = text('E-Mail', required: true, validate: fn ($v) => Validator::make(
            ['email' => $v],
            ['email' => 'email|unique:users,email']
        )->errors()->first('email') ?: null);
        $password = password('Passwort (mind. 10 Zeichen)', required: true, validate: fn ($v) => strlen($v) < 10 ? 'Mindestens 10 Zeichen.' : null);

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->info("Konto für {$email} angelegt. Anmeldung unter ".rtrim(config('app.url'), '/').'/admin');

        return self::SUCCESS;
    }
}
