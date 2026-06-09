<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menghapus batasan check (enum) bawaan Laravel di PostgreSQL
        // Agar kolom payment_method bisa diisi string apa saja (cash, midtrans, qris, dll)
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_payment_method_check');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Jika di-rollback, kembalikan constraint-nya
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_method_check CHECK (payment_method::text = ANY (ARRAY['cash'::character varying, 'midtrans_qris'::character varying, 'midtrans_transfer'::character varying]::text[]))");
    }
};
