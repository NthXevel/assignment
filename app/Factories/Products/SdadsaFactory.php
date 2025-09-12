<?php

namespace App\Factories\Products;

use App\Models\Product;

/**
 * 
 */
class SdadsaFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        $data['specifications'] = array_merge($data['specifications'] ?? [], array (
  'dasdas' => 'dasdas',
));

        return Product::create($data);
    }
}