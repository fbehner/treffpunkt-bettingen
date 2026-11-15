<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('kicker')->nullable()->after('slug');
            $table->string('headline')->nullable()->after('kicker');
            $table->string('headline_accent')->nullable()->after('headline');
            $table->text('lead')->nullable()->after('headline_accent');
            $table->json('blocks')->nullable()->after('body');
            $table->string('meta_description')->nullable()->after('blocks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['kicker', 'headline', 'headline_accent', 'lead', 'blocks', 'meta_description']);
        });
    }
};
