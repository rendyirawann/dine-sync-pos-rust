<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'uuid',
        'invoice_no',
        'table_id',
        'customer_name',
        'order_type',
        'subtotal',
        'tax',
        'grand_total',
        'payment_method',
        'payment_status',
        'order_status',
        'snap_token',
        'promo_id',
        'discount_amount'
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }
}
