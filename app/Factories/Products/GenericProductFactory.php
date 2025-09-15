<?php
// Author: Ho Jie Han
namespace App\Factories\Products;

use App\Models\Product;

class GenericProductFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }
}
