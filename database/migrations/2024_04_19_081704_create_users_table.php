<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {

            $table->id();
            $table->string('uuid');

            $table->binary('email')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->nullable();

            $table->string('otp')->nullable();
            $table->string('authType')->nullable();
            $table->timestamp('otp_expiry')->nullable();

            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_location')->nullable();

            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('provider_token')->nullable();

            $table->string('two_factor_code')->nullable();
            $table->string('two_factor_expires_at')->nullable();
            $table->string('sms_number')->nullable();
            $table->string('two_fa_status')->nullable();

            $table->text('refreshToken')->nullable();

            $table->foreignId('company_id')->references('id')->on('companies')->onDelete('cascade')->nullable();

            $table->rememberToken();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
