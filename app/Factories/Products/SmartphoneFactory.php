<?php
// Author: Ho Jie Han
namespace App\Factories\Products;

use App\Models\Product;

class SmartphoneFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        $data['specifications'] = array_merge($data['specifications'] ?? [], [
            'type' => 'smartphone',
            'warranty_months' => 24,
            'return_policy_days' => 14
        ]);

        return Product::create($data);
    }
}
