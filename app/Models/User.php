<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, InteractsWithMedia;
    
    protected $fillable = [
        'username', 'email', 'password', 'branch_id', 'role', 'is_active'
    ];
    
    protected $hidden = ['password', 'remember_token'];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    public function orders()
    {
        return $this->hasMany(Order::class, 'created_by');
    }
    
    // Decorator Pattern Implementation
    public function hasPermission($permission)
    {
        $rolePermissions = [
            'admin' => ['*'],
            'stock_manager' => ['manage_stock', 'view_products', 'approve_orders'],
            'order_creator' => ['create_orders', 'view_orders', 'view_products'],
            'branch_manager' => ['create_orders', 'view_orders', 'view_products', 'manage_branch_stock']
        ];
        
        $permissions = $rolePermissions[$this->role] ?? [];
        
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
    
    public function canAccessMainBranchFeatures()
    {
        return $this->branch->is_main && in_array($this->role, ['admin', 'stock_manager']);
    }
}
