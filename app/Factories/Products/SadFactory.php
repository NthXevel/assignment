<?php

namespace App\Factories\Products;

use App\Models\Product;

/**
 * 
 */
class SadFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        $data['specifications'] = array_merge($data['specifications'] ?? [], array (
  'dsad' => 'asdas',
));

        return Product::create($data);
    }
}