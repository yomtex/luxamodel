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
        Schema::table('users', function (Blueprint $table) {
            $table->float('height')->nullable();
            $table->float('weight')->nullable();
            $table->string('skin_color')->nullable();
            $table->string('hair_color')->nullable();
            $table->string('hair_length')->nullable();
            $table->string('eye_color')->nullable();
            $table->string('ethnicity')->nullable();
            $table->boolean('tattoos')->default(false);
            $table->boolean('piercing')->default(false);
            $table->string('compensation')->nullable();
            $table->string('experience')->nullable();
            $table->boolean('shoot_nudes')->default(false);

            // Female-specific
            $table->float('bust')->nullable();
            $table->float('hips')->nullable();
            $table->float('dresswaist')->nullable();
            $table->string('cup')->nullable();
            $table->float('shoe')->nullable();

            // Male-specific
            $table->float('chest')->nullable();
            $table->float('inseam')->nullable();
            $table->float('neck')->nullable();
            $table->float('sleeve')->nullable();
            $table->float('waist')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'height', 'weight', 'skin_color', 'hair_color', 'hair_length',
                'eye_color', 'ethnicity', 'tattoos', 'piercing', 'compensation',
                'experience', 'shoot_nudes', 'bust', 'hips', 'dresswaist', 'cup', 'shoe',
                'chest', 'inseam', 'neck', 'sleeve', 'waist'
            ]);
        });
    }


};
