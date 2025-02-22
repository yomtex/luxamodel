<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Link to user table
            $table->string('payment_method');  // Card type or payment method (e.g., credit card)
            $table->decimal('amount', 10, 2);  // Amount paid
            $table->string('status')->default('pending');  // Payment status (e.g., 'success', 'failed')
            $table->string('plan_type');  // Store which plan the user selected ('basic' or 'vip')
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_histories');
    }
}


