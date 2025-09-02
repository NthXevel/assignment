<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'requesting_branch_id', 'supplying_branch_id', 
        'created_by', 'status', 'priority', 'total_amount', 'notes'
    ];
    
    protected $casts = [
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];
    
    public function requestingBranch()
    {
        return $this->belongsTo(Branch::class, 'requesting_branch_id');
    }
    
    public function supplyingBranch()
    {
        return $this->belongsTo(Branch::class, 'supplying_branch_id');
    }
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    protected static function booted()
    {
        static::creating(function ($order) {
            $order->order_number = 'ORD-' . date('Y') . '-' . str_pad(
                Order::whereYear('created_at', date('Y'))->count() + 1, 
                6, '0', STR_PAD_LEFT
            );
        });
    }
    
    public function calculateTotal()
    {
        $this->total_amount = $this->items()->sum('total_price');
        $this->save();
    }
    
    public function approve()
    {
        $this->status = 'approved';
        $this->approved_at = now();
        $this->save();
    }
    
    public function ship()
    {
        $this->status = 'shipped';
        $this->shipped_at = now();
        $this->save();
        
        // Reduce stock at supplying branch
        foreach ($this->items as $item) {
            $stock = Stock::where('product_id', $item->product_id)
                         ->where('branch_id', $this->supplying_branch_id)
                         ->first();
            if ($stock) {
                $stock->updateQuantity(-$item->quantity, 'Order shipped: ' . $this->order_number);
            }
        }
    }
    
    public function receive()
    {
        $this->status = 'received';
        $this->received_at = now();
        $this->save();
        
        // Add stock at requesting branch
        foreach ($this->items as $item) {
            $stock = Stock::firstOrCreate([
                'product_id' => $item->product_id,
                'branch_id' => $this->requesting_branch_id
            ], ['quantity' => 0]);
            
            $stock->updateQuantity($item->quantity, 'Order received: ' . $this->order_number);
        }
    }
}

