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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->binary('username')->nullable();

            $table->binary('phone')->nullable();
            $table->binary('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->binary('country')->nullable();
            $table->binary('id_number')->nullable();

            $table->binary('address')->nullable();
            $table->text('profileImage')->nullable();
            $table->binary('location')->nullable();
            $table->binary('about_the_user')->nullable();

            $table->string('is_verified');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
