<?php

namespace App\Factories\Products;

use App\Models\Product;

/**
 * General Specification of Xiaomi Phone
 */
class XiaomiPhoneFactory extends ProductFactory
{
    public function createProduct(array $data): Product
    {
        $data['specifications'] = array_merge($data['specifications'] ?? [], array (
  'Warranty (Year)' => 1,
  'Waterproof' => 'IPX6 and above',
  '3.5 mm headphone jack' => 'yes',
));

        return Product::create($data);
    }
}