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
        Schema::create('home_payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');

            $table->unsignedBigInteger('home_id');
            $table->unsignedBigInteger('payment_type_id');

            $table->string('account_name');
            $table->string('account_payBill')->nullable();
            $table->string('account_number');

            $table->foreign('home_id')->references('id')->on('homes')->onDelete('cascade');
            $table->foreign('payment_type_id')->references('id')->on('payment_types')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_payment_types');
    }
};
