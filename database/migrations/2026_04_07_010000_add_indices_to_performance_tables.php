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
        // 1. Tabel Orders (Sangat Penting karena datanya paling banyak)
        Schema::table('orders', function (Blueprint $table) {
            $table->index('payment_status');
            $table->index('created_at');
        });

        // 2. Tabel Expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('date');
        });

        // 3. Tabel Daily Sales Targets
        Schema::table('daily_sales_targets', function (Blueprint $table) {
            $table->index('date');
        });

        // 4. Tabel Daily Budgets
        Schema::table('daily_budgets', function (Blueprint $table) {
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['date']);
        });

        Schema::table('daily_sales_targets', function (Blueprint $table) {
            $table->dropIndex(['date']);
        });

        Schema::table('daily_budgets', function (Blueprint $table) {
            $table->dropIndex(['date']);
        });
    }
};
