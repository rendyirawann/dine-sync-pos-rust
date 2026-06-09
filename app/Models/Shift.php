<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // Jangan lupa import ini

class Shift extends Model
{
    // Tambahkan 'uuid' di dalam fillable
    protected $fillable = [
        'uuid',
        'user_id',
        'start_time',
        'end_time',
        'starting_cash',
        'cash_sales',
        'expected_cash',
        'actual_cash',
        'difference',
        'status'
    ];

    // Boot function untuk generate UUID otomatis (Sama persis seperti di Sale.php)
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
