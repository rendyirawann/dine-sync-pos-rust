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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_no')->unique();
            $table->foreignId('table_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0); // Pajak Resto 10%
            $table->decimal('grand_total', 15, 2)->default(0);

            // Status Pembayaran & Metode
            $table->enum('payment_method', ['cash', 'midtrans_qris', 'midtrans_transfer'])->nullable();
            $table->enum('payment_status', ['unpaid', 'paid', 'failed'])->default('unpaid');

            // Status Pesanan (Untuk Kitchen Display)
            $table->enum('order_status', ['pending', 'cooking', 'served', 'completed'])->default('pending');
            $table->string('snap_token')->nullable(); // Token dari Midtrans
            $table->timestamps();
        });

        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->integer('qty');
            $table->decimal('price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->string('notes')->nullable(); // Contoh: "Jangan pakai daun bawang"
            $table->enum('status', ['pending', 'cooking', 'done'])->default('pending'); // Status per item masakan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
