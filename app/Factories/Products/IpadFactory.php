<?php

namespace App\Factories\Products;

use App\Models\Product;

/**
 * 
 */
class IpadFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        $data['specifications'] = array_merge($data['specifications'] ?? [], array (
  'storage' => '128GB',
));

        return Product::create($data);
    }
}