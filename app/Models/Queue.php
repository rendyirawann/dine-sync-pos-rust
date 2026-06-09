<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    // 🔥 WAJIB DITAMBAHKAN: Mengizinkan Laravel mengisi kolom-kolom ini
    protected $fillable = [
        'queue_number',
        'customer_name',
        'pax',
        'status'
    ];
}
