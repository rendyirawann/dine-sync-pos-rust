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
        // 1. Membuat Tabel Users
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Menggunakan UUID
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('no_wa', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('last_login')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('phone', 15)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Membuat Tabel Password Reset Tokens (Bawaan Laravel)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. Membuat Tabel Sessions (Ini yang hilang!)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // PENTING: foreign_id untuk session harus uuid karena tabel users pakai uuid
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
