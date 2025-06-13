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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 20);
            $table->string('last_name', 20);
            $table->enum('role', ["manager","employee"]);
            $table->string('email', 100);
            $table->string('password');
            $table->string('username', 20)->nullable();
            $table->string('phone_number', 15);
            $table->string('avatar', 100);
            $table->boolean('is_verified')->default(false);
            $table->string('otp', 10)->nullable();
            $table->timestamp('otp_expire_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
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
