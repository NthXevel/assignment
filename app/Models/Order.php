<?php
// Author: Lee Kai Yi
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
        'notes',
        'approved_at',
        'shipped_at',
        'received_at',
        'sla_due_at',
        'total_amount',
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

    // Accessor: automatically decrypt order_number when accessed
    public function getOrderNumberAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return $value; // fallback if not encrypted
        }
    }

    // Mutator: automatically encrypt order_number when saving
    public function setOrderNumberAttribute($value)
    {
        $this->attributes['order_number'] = Crypt::encryptString($value);
    }
    // Dynamic total amount (calculated from items)
    public function getTotalAmountAttribute()
    {
        return $this->items->sum('total_price');
    }
}
