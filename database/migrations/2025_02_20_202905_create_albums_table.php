<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Ensure consistency

            $table->string('name');
            $table->boolean('hidden')->default(false);
            $table->enum('privacy', ['public', 'password'])->default('public');
            $table->string('password')->nullable(); // Nullable for 'password' privacy

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('albums');
    }
};


