<?php

namespace App\Factories\Products;

use App\Models\Product;

/**
 * 
 */
class NewIphoneFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        $data['specifications'] = array_merge($data['specifications'] ?? [], array (
  'color' => '23',
));

        return Product::create($data);
    }
}