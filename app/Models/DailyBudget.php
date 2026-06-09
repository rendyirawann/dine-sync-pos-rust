<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyBudget extends Model
{
    protected $fillable = ['date', 'amount'];
}
