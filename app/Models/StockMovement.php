<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'stock_id',
        
        'quantity_change',
        'reason',
        'balance_after',
    ];
    
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    // Access product through stock
    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            Stock::class,
            'id',        // Stock's primary key
            'id',        // Product's primary key
            'stock_id',  // FK on stock_movements
            'product_id' // FK on stocks
        );
    }

    // Access branch through stock
    public function branch()
    {
        return $this->hasOneThrough(
            Branch::class,
            Stock::class,
            'id',
            'id',
            'stock_id',
            'branch_id'
        );
    }
}
