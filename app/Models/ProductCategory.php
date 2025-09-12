<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Factories\Products\ProductFactory;

class ProductCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'factory_type'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function getFactoryInstance(): ProductFactory
    {
        // Delegate resolution to central ProductFactory mapping which also
        // supports dynamic class discovery and safe fallback.
        return ProductFactory::getFactory($this->factory_type);
    }
}


