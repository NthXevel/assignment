<?php
// Author: Ho Jie Han
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
  'test' => '128GB',
));

        return Product::create($data);
    }
}