<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('agency_name');
            $table->string('email')->unique();
            $table->string('official_mail')->unique();
            $table->string('category');
            $table->string('phone_number');
            $table->string('website')->nullable();
            $table->text('about_agency');
            $table->text('address');
            $table->string('country');
            $table->string('agency_logo')->nullable();
            $table->json('contact_details'); // Store social media & contacts as JSON
            $table->enum('status', ['pending', 'verified'])->default('pending'); // Admin verification
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::dropIfExists('agencies');
    }
};
