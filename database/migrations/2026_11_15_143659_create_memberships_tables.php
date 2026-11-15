<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mitgliedschaften (Rechnungseinheit), zugehörige Personen und Jahresbeiträge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('number')->unique();
            $table->string('type');
            $table->unsignedInteger('fee');
            $table->string('organisation')->nullable();
            $table->string('street')->nullable();
            $table->string('zip_city')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->string('exit_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('left_at');
        });

        Schema::create('membership_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->string('relation')->default('haupt');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->date('birthdate')->nullable();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('membership_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('donation')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['membership_id', 'year']);
            $table->index(['year', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_fees');
        Schema::dropIfExists('membership_people');
        Schema::dropIfExists('memberships');
    }
};
