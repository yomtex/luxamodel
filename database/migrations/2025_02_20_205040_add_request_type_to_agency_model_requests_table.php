<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('agency_model_requests', function (Blueprint $table) {
            $table->enum('request_type', ['discover', 'claim'])->default('discover')->after('agency_id');
        });
    }

    public function down()
    {
        Schema::table('agency_model_requests', function (Blueprint $table) {
            $table->dropColumn('request_type');
        });
    }
};
