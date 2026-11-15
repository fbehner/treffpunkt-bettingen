<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL/MariaDB setzen eine TIMESTAMP-Spalte ohne Standardwert je nach
 * Servereinstellung bei jeder Änderung der Zeile auf „jetzt“
 * (ON UPDATE CURRENT_TIMESTAMP). Dadurch liefen Einladungen sofort ab.
 * DATETIME kennt dieses Verhalten nicht.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable()->change();
        });

        // Offene Einladungen reparieren: gültig ab letztem Versand.
        foreach (DB::table('invitations')->whereNull('accepted_at')->get() as $invitation) {
            $from = Carbon::parse($invitation->sent_at ?? $invitation->created_at);
            DB::table('invitations')->where('id', $invitation->id)->update([
                'expires_at' => $from->addDays(14)->toDateTimeString(),
            ]);
        }
    }

    public function down(): void
    {
        // Bewusst kein Zurück auf TIMESTAMP.
    }
};
