<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Herkunft eines Gesuchs (Website, Papier, E-Mail, persönlich) und Verknüpfung
 * mit der daraus entstandenen Mitgliedschaft.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->string('source')->default('web')->after('type');
            $table->date('received_at')->nullable()->after('source');
            $table->foreignId('membership_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_id');
            $table->dropColumn(['source', 'received_at']);
        });
    }
};
