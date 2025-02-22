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
       Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->foreignId('album_id')->constrained('albums')->onDelete('cascade'); // Ensures correct table reference
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
