<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersTableAddProfileAndPlanFields extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo')->nullable(); // Profile photo
            $table->text('bio')->nullable(); // Optional bio
            $table->enum('plan_type', ['free', 'basic', 'vip'])->default('free'); // Plan type: free or paid
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_photo', 'bio', 'plan_type']);
        });
    }
}

