<?php

namespace App\Factories\Products;

use App\Models\Product;

class WearableFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        $data['specifications'] = array_merge($data['specifications'] ?? [], [
            'type' => 'wearable',
            'warranty_months' => 12,
            'water_resistant' => true
        ]);

        return Product::create($data);
    }
}
