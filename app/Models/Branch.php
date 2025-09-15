<?php
// Author: Clive Lee Ee Xuan
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'location', 'status', 'is_main'];
    
    protected $attributes = [
        'is_main' => false,
        'status' => 'active'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
    
    public function requestedOrders()
    {
        return $this->hasMany(Order::class, 'requesting_branch_id');
    }
    
    public function suppliedOrders()
    {
        return $this->hasMany(Order::class, 'supplying_branch_id');
    }
    
    public static function getMainBranch()
    {
        return self::where('is_main', true)->first();
    }
}

