<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: Diskon Karyawan, Promo Kemerdekaan
            $table->enum('discount_type', ['percentage', 'nominal']); // persen atau potongan harga
            $table->integer('discount_value'); // Contoh: 10 (jika 10%), atau 50000 (jika potongan 50rb)
            $table->boolean('is_active')->default(true); // Status aktif/non-aktif
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
