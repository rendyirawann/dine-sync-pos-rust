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
        Schema::create('daily_sales_targets', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique(); // 1 hari hanya ada 1 target
            $table->decimal('amount', 15, 2)->default(0); // Jumlah target Rupiah
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sales_targets');
    }
};
