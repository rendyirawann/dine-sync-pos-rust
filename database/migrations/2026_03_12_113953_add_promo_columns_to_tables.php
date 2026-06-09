<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom diskon di tabel Orders (Nota)
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('promo_id')->nullable()->after('table_id')->constrained('promos')->nullOnDelete();
            $table->integer('discount_amount')->default(0)->after('subtotal');
        });

        // Tambah kolom diskon khusus per-menu di tabel Menus
        Schema::table('menus', function (Blueprint $table) {
            $table->integer('discount_percent')->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['promo_id']);
            $table->dropColumn(['promo_id', 'discount_amount']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }
};
