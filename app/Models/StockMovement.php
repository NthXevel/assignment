<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = ['stock_id', 'quantity_change', 'reason', 'balance_after'];
    
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}

