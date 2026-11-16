<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Raumreservationen: Anfragen über die Website und Einträge aus dem Backoffice.
 * Läuft auf SQLite und MySQL 5.7 (keine JSON-Defaults, keine Funktions-Defaults).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('package', 20);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('angefragt');
            $table->string('source', 20)->default('web');

            // Anlass
            $table->string('purpose', 200);
            $table->string('public_title', 80)->nullable();
            $table->unsignedSmallInteger('participants')->nullable();

            // Kontakt
            $table->string('organisation', 160)->nullable();
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('street', 120)->nullable();
            $table->string('zip_city', 120)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 40)->nullable();

            // Tarif
            $table->boolean('is_member')->default(false);
            $table->string('member_number', 20)->nullable();
            $table->unsignedInteger('price')->nullable();

            $table->text('message')->nullable();
            $table->text('notes')->nullable();
            $table->text('decision_note')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('house_rules_accepted_at')->nullable();
            $table->dateTime('privacy_accepted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_reservations');
    }
};
