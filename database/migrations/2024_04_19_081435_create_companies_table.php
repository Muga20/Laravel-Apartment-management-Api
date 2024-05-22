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
        Schema::create('companies', function (Blueprint $table) {

            $table->id();
            $table->string('companyId');
            $table->binary('name');
            $table->binary('email')->nullable();
            $table->string('status');
            $table->binary('address')->nullable();
            $table->binary('phone')->nullable();
            $table->binary('description')->nullable();
            $table->string('theme')->nullable();
            $table->binary('logoImage')->nullable();
            $table->binary('slug');
            $table->binary('location')->nullable();
            $table->binary('companyUrl')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};


