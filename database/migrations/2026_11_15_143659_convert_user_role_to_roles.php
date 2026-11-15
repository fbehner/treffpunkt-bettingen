<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ein Konto kann mehrere Rollen haben (Webadmin, Redaktion, Mitgliederverwaltung).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('password');
        });

        foreach (DB::table('users')->get(['id', 'role']) as $user) {
            DB::table('users')->where('id', $user->id)->update(['roles' => json_encode([$user->role ?: 'redaktion'])]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('redaktion')->after('password');
        });

        foreach (DB::table('users')->get(['id', 'roles']) as $user) {
            $roles = json_decode($user->roles ?? '[]', true) ?: ['redaktion'];
            DB::table('users')->where('id', $user->id)->update(['role' => in_array('admin', $roles, true) ? 'admin' : $roles[0]]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
