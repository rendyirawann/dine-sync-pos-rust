<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySalesTarget extends Model
{
    protected $fillable = ['date', 'amount'];
}
