<?php
// Author: Ho Jie Han
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;


class Product extends Model
{
    protected $fillable = [
        'name', 'model', 'sku', 'category_id', 'cost_price', 
        'selling_price', 'description', 'specifications', 'is_active'
    ];
    
    protected $casts = [

        'selling_price' => 'decimal:2',
        'specifications' => 'json',
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

   // Encrypt cost_price before saving
    public function setCostPriceAttribute($value)
    {
        $this->attributes['cost_price'] = Crypt::encrypt($value);
    }

    // Decrypt cost_price when accessing
    public function getCostPriceAttribute($value)
    {
        return Crypt::decrypt($value);
    }


}


