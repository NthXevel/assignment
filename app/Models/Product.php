<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'model', 'sku', 'category_id', 'cost_price', 
        'selling_price', 'description', 'specifications', 'is_active'
    ];
    
    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'specifications' => 'array',
        'is_active' => 'boolean',
    ];
    
    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }
    
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
    
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    public function getMainBranchStock()
    {
        $mainBranch = Branch::getMainBranch();
        return $this->stocks()->where('branch_id', $mainBranch->id)->first();
    }
}


