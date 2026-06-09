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
        // 1. Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 2. Ingredients (Master Data)
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit'); // gram, ml, pcs, slice, etc.
            $table->decimal('minimum_stock', 15, 2)->default(0);
            $table->timestamps();
        });

        // 3. Ingredient Batches (FIFO Support)
        Schema::create('ingredient_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('initial_quantity', 15, 2);
            $table->decimal('remaining_quantity', 15, 2);
            $table->decimal('buy_price', 15, 2); // Price per unit
            $table->date('entry_date');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        // 4. Recipe (Linking Menus to Ingredients)
        Schema::create('menu_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 2); // Grammage/Amount used per portion
            $table->timestamps();
        });

        // 5. Stock Movements (Logs)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->foreignId('ingredient_batch_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['in', 'out']);
            $table->decimal('quantity', 15, 2);
            $table->string('reason'); // 'purchase', 'sales_deduction', 'adjustment', 'waste'
            $table->string('reference')->nullable(); // Order Invoice No or Purchase ID
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('menu_ingredients');
        Schema::dropIfExists('ingredient_batches');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('suppliers');
    }
};
