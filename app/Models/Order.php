<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'requesting_branch_id',
        'supplying_branch_id',
        'created_by',
        'status',
        'priority',
        'total_amount',
        'notes',
        'approved_at',
        'shipped_at',
        'received_at',
    ];

    // Relationships
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

    // 🔹 Business logic methods
    public function approve()
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Only pending orders can be approved');
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('branch_id', $this->supplying_branch_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantity < $item->quantity) {
                    throw new \Exception("Insufficient stock for {$item->product->name}");
                }

                // Deduct stock at supplying branch
                $stock->decrement('quantity', $item->quantity);
            }

            $this->status = 'approved';
            $this->approved_at = now();
            $this->save();
        });
    }

    public function ship()
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Order must be approved before shipping');
        }

        $this->status = 'shipped';
        $this->shipped_at = now();
        $this->save();
    }

    public function receive()
    {
        if ($this->status !== 'shipped') {
            throw new \Exception('Order must be shipped before receiving');
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $stock = Stock::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'branch_id' => $this->requesting_branch_id,
                    ],
                    ['quantity' => 0]
                );

                // Add stock to requesting branch
                $stock->increment('quantity', $item->quantity);
            }

            $this->status = 'received';
            $this->received_at = now();
            $this->save();
        });
    }

    public function cancel()
    {
        if (!in_array($this->status, ['pending', 'approved'])) {
            throw new \Exception('Cannot cancel shipped or received orders');
        }

        DB::transaction(function () {
            // If already approved, return stock to supplying branch
            if ($this->status === 'approved') {
                foreach ($this->items as $item) {
                    $stock = Stock::where('product_id', $item->product_id)
                        ->where('branch_id', $this->supplying_branch_id)
                        ->lockForUpdate()
                        ->first();

                    if ($stock) {
                        $stock->increment('quantity', $item->quantity);
                    }
                }
            }

            $this->status = 'cancelled';
            $this->save();
        });
    }

    // 🔹 Accessor: automatically decrypt order_number when accessed
    public function getOrderNumberAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return $value; // fallback if not encrypted
        }
    }

    // 🔹 Mutator: automatically encrypt order_number when saving
    public function setOrderNumberAttribute($value)
    {
        $this->attributes['order_number'] = Crypt::encryptString($value);
    }
}
