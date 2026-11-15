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
        Schema::create('membership_applications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedInteger('fee');
            $table->string('organisation')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birthdate')->nullable();
            $table->string('partner_first_name')->nullable();
            $table->string('partner_last_name')->nullable();
            $table->text('children')->nullable();
            $table->string('street');
            $table->string('zip_city');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->string('guardian_name')->nullable();
            $table->string('guardian_email')->nullable();
            $table->timestamp('guardian_confirmed_at')->nullable();
            $table->unsignedInteger('donation')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('statutes_accepted_at')->nullable();
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->string('status')->default('eingegangen');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_applications');
    }
};
