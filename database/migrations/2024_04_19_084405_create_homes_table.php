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
        Schema::create('homes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('location');
            $table->text('images')->nullable();
            $table->string('houseCategory');
            $table->string('stories')->nullable();
            $table->string('status')->nullable();
            $table->string('description')->nullable();
            $table->string('rentPaymentDay')->nullable();

            $table->unsignedBigInteger('landlord_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();

            $table->foreign('landlord_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homes');
    }
};
