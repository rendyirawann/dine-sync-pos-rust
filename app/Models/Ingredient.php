<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = ['name', 'unit', 'minimum_stock'];

    public function batches()
    {
        return $this->hasMany(IngredientBatch::class);
    }

    public function recipes()
    {
        return $this->hasMany(MenuIngredient::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // Helper to get total remaining stock across all batches
    public function currentStock()
    {
        return $this->batches()->sum('remaining_quantity');
    }
}
