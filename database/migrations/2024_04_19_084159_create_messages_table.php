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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->binary('subject')->nullable();
            $table->binary('message')->nullable();
            $table->string('status');
            $table->foreignId('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreignId('senders_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('recipients_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};





