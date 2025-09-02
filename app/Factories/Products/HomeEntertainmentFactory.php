<?php

namespace App\Factories\Products;

use App\Models\Product;

class HomeEntertainmentFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        $data['specifications'] = array_merge($data['specifications'] ?? [], [
            'type' => 'home_entertainment',
            'warranty_months' => 12,
            'installation_available' => true
        ]);

        return Product::create($data);
    }
}
