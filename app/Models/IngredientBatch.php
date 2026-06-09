<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientBatch extends Model
{
    protected $fillable = [
        'ingredient_id',
        'supplier_id',
        'initial_quantity',
        'remaining_quantity',
        'buy_price',
        'buy_price_total',
        'entry_date',
        'expiry_date'
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
