<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Doppelt eingereichte Gesuche verweisen auf das massgebliche Gesuch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->foreignId('duplicate_of_id')->nullable()->after('membership_id')
                ->constrained('membership_applications')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicate_of_id');
        });
    }
};
