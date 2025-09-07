<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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

    // Actions
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
    }

    public function receive()
    {
        $this->status = 'received';
        $this->received_at = now();
        $this->save();
    }

    // Accessor: automatically decrypt order_number when accessed
    public function getOrderNumberAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Fallback if value is not encrypted
            return $value;
        }
    }

    // Mutator: automatically encrypt order_number when saving
    public function setOrderNumberAttribute($value)
    {
        $this->attributes['order_number'] = Crypt::encryptString($value);
    }
}
