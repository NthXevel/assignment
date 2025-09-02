<?php

namespace App\Factories\Products;

use App\Models\Product;

abstract class ProductFactory
{
    abstract public function createProduct(array $data): Product;

    public static function getFactory(string $categorySlug): ProductFactory
    {
        return match ($categorySlug) {
            'smartphones-accessories' => new SmartphoneFactory(),
            'computers-peripherals'   => new ComputerFactory(),
            'home-entertainment'      => new HomeEntertainmentFactory(),
            'wearables-smart-devices' => new WearableFactory(),
            default                   => new GenericProductFactory(),
        };
    }
}
