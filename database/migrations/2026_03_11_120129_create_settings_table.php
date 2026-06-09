<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('DineSync POS');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->integer('tax_rate')->default(10); // Disimpan dalam bentuk persen (Contoh: 10, 11, atau 0)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
