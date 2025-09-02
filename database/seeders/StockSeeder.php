<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Stock;


class StockSeeder extends Seeder
{
    public function run()
    {
        $products = Product::all();
        $branches = Branch::all();
        $mainBranch = Branch::where('is_main', true)->first();
        
        foreach ($products as $product) {
            $this->createStockForProduct($product, $branches, $mainBranch);
        }
    }
    
    private function createStockForProduct(Product $product, $branches, Branch $mainBranch)
    {
        // Determine stock levels based on product category and price
        $stockLevels = $this->getStockLevelsForProduct($product);
        
        foreach ($branches as $branch) {
            // Main branch gets higher stock levels
            if ($branch->is_main) {
                $quantity = $stockLevels['main_branch'];
                $minThreshold = $stockLevels['main_threshold'];
            } else {
                // Other branches get varied stock levels
                $quantity = $this->getRandomStockForBranch($stockLevels['other_branches']);
                $minThreshold = $stockLevels['other_threshold'];
            }
            
            Stock::create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'quantity' => $quantity,
                'minimum_threshold' => $minThreshold,
            ]);
        }
    }
    
    private function getStockLevelsForProduct(Product $product): array
    {
        $category = $product->category;
        $price = $product->cost_price;
        
        // Base stock levels on product category and price range
        switch ($category->slug) {
            case 'smartphones-accessories':
                if ($price > 3000) {
                    // High-end phones (lower stock)
                    return [
                        'main_branch' => rand(25, 45),
                        'main_threshold' => 10,
                        'other_branches' => [3, 12],
                        'other_threshold' => 3,
                    ];
                } elseif ($price > 1000) {
                    // Mid-range phones
                    return [
                        'main_branch' => rand(40, 70),
                        'main_threshold' => 15,
                        'other_branches' => [5, 18],
                        'other_threshold' => 5,
                    ];
                } else {
                    // Accessories (higher stock)
                    return [
                        'main_branch' => rand(80, 150),
                        'main_threshold' => 25,
                        'other_branches' => [10, 35],
                        'other_threshold' => 8,
                    ];
                }
                
            case 'computers-peripherals':
                if ($price > 5000) {
                    // High-end laptops (lower stock)
                    return [
                        'main_branch' => rand(15, 30),
                        'main_threshold' => 8,
                        'other_branches' => [2, 8],
                        'other_threshold' => 2,
                    ];
                } elseif ($price > 1000) {
                    // Mid-range computers and monitors
                    return [
                        'main_branch' => rand(25, 50),
                        'main_threshold' => 12,
                        'other_branches' => [3, 15],
                        'other_threshold' => 4,
                    ];
                } else {
                    // Peripherals (higher stock)
                    return [
                        'main_branch' => rand(60, 120),
                        'main_threshold' => 20,
                        'other_branches' => [8, 30],
                        'other_threshold' => 6,
                    ];
                }
                
            case 'home-entertainment':
                if ($price > 5000) {
                    // Large TVs and premium systems (lower stock)
                    return [
                        'main_branch' => rand(10, 25),
                        'main_threshold' => 5,
                        'other_branches' => [1, 6],
                        'other_threshold' => 2,
                    ];
                } elseif ($price > 2000) {
                    // Gaming consoles, soundbars
                    return [
                        'main_branch' => rand(20, 40),
                        'main_threshold' => 10,
                        'other_branches' => [2, 12],
                        'other_threshold' => 3,
                    ];
                } else {
                    // Streaming devices, smaller speakers
                    return [
                        'main_branch' => rand(50, 90),
                        'main_threshold' => 18,
                        'other_branches' => [6, 25],
                        'other_threshold' => 5,
                    ];
                }
                
            case 'wearables-smart-devices':
                if ($price > 1500) {
                    // Premium smartwatches (moderate stock)
                    return [
                        'main_branch' => rand(30, 55),
                        'main_threshold' => 12,
                        'other_branches' => [4, 15],
                        'other_threshold' => 4,
                    ];
                } elseif ($price > 500) {
                    // Mid-range wearables
                    return [
                        'main_branch' => rand(45, 80),
                        'main_threshold' => 15,
                        'other_branches' => [6, 20],
                        'other_threshold' => 5,
                    ];
                } else {
                    // Smart home devices, basic fitness trackers
                    return [
                        'main_branch' => rand(70, 130),
                        'main_threshold' => 22,
                        'other_branches' => [8, 35],
                        'other_threshold' => 7,
                    ];
                }
                
            default:
                // Default stock levels
                return [
                    'main_branch' => rand(30, 60),
                    'main_threshold' => 15,
                    'other_branches' => [5, 20],
                    'other_threshold' => 5,
                ];
        }
    }
    
    private function getRandomStockForBranch(array $range): int
    {
        // Create some variation in stock levels across branches
        $baseStock = rand($range[0], $range[1]);
        
        // Add some randomness to create realistic stock scenarios
        $variation = rand(-30, 50); // Can be negative to create low stock situations
        $finalStock = max(0, $baseStock + ($baseStock * $variation / 100));
        
        return (int) $finalStock;
    }
}
