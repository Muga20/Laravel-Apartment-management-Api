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
        Schema::create('unit_records', function (Blueprint $table) {
            $table->id();

            $table->double('rentFee',8,2)->nullable();
            $table->double('garbageFee',8,2)->nullable();
            $table->double('waterFee',8,2)->nullable();

            $table->string('phone')->nullable();
            $table->string('acc_number')->nullable();
            $table->date('transaction_date');
            $table->string('receipt');
            $table->text('receiptInput')->nullable();

            $table->string('status');
            $table->string('isApproved')->nullable();

            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('tenant_id')->nullable();

            $table->unsignedBigInteger('payment_type_id');
            $table->unsignedBigInteger('stkPush_id')->nullable();


            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('payment_type_id')->references('id')->on('home_payment_types')->onDelete('cascade');
            $table->foreign('stkPush_id')->references('id')->on('stkrequests')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_records');
    }
};

