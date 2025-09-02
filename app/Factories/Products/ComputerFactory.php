<?php

namespace App\Factories\Products;

use App\Models\Product;

class ComputerFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        $data['specifications'] = array_merge($data['specifications'] ?? [], [
            'type' => 'computer',
            'warranty_months' => 36,
            'return_policy_days' => 30
        ]);

        return Product::create($data);
    }
}
