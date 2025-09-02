<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class ProductCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Smartphones and Accessories',
                'slug' => 'smartphones-accessories',
                'description' => 'Mobile phones, cases, chargers, and accessories'
            ],
            [
                'name' => 'Computers and Peripherals',
                'slug' => 'computers-peripherals',
                'description' => 'Laptops, desktops, keyboards, mice, and computer accessories'
            ],
            [
                'name' => 'Home Entertainment Systems',
                'slug' => 'home-entertainment',
                'description' => 'TVs, speakers, gaming consoles, and entertainment devices'
            ],
            [
                'name' => 'Wearables and Smart Devices',
                'slug' => 'wearables-smart-devices',
                'description' => 'Smartwatches, fitness trackers, and IoT devices'
            ],
        ];
        
        foreach ($categories as $category) {
            ProductCategory::create($category);
        }
    }
}

