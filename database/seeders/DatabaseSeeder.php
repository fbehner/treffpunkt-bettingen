<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ContentSeeder::class);

        // Testkonto nur für die lokale Entwicklung.
        if (app()->isLocal()) {
            User::firstOrCreate(
                ['email' => 'admin@example.test'],
                ['name' => 'Test Admin', 'password' => 'passwort123', 'roles' => ['admin', 'mitglieder']],
            );
        }
    }
}
