<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use SplObserver;
class Stock extends Model implements \SplSubject
{
    use HasFactory;
    
    protected $fillable = ['product_id', 'branch_id', 'quantity', 'minimum_threshold'];
    
    protected static $observers = [];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    // Observer Pattern Implementation
    public function attach(SplObserver $observer)
    {
        self::$observers[] = $observer;
    }
    
    public function detach(SplObserver $observer)
    {
        $key = array_search($observer, self::$observers);
        if ($key !== false) {
            unset(self::$observers[$key]);
        }
    }
    
    public function notify()
    {
        foreach (self::$observers as $observer) {
            $observer->update($this);
        }
    }
    
    protected static function booted()
    {
        static::updated(function ($stock) {
            if ($stock->isLowStock()) {
                $stock->notify();
            }
        });
    }
    
    public function isLowStock()
    {
        return $this->quantity <= $this->minimum_threshold;
    }
    
    public function updateQuantity($change, $reason = null)
    {
        $this->quantity += $change;
        $this->save();
        
        // Log stock movement
        StockMovement::create([
            'stock_id' => $this->id,
            'quantity_change' => $change,
            'reason' => $reason,
            'balance_after' => $this->quantity,
        ]);
    }
}

