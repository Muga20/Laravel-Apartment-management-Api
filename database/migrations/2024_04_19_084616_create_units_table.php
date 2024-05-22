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
        Schema::create('units', function (Blueprint $table) {
            $table->id();

            $table->string('unit_name');
            $table->string('slug');
            $table->string('number_of_rooms')->nullable();

            $table->double('payableRent',8,2)->nullable();
            $table->double('lastWaterBill',8,2)->nullable();
            $table->double('lastGarbageBill',8,2)->nullable();

            $table->string('paymentPeriod')->nullable();
            $table->string('paymentNumber')->nullable();
            $table->text('damages')->nullable();

            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('home_id');

            $table->string('status');
            $table->string('isPaid')->nullable();

            $table->foreign('agent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('home_id')->references('id')->on('homes')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
