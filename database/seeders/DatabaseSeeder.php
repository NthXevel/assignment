<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            BranchSeeder::class,
            ProductCategorySeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            StockSeeder::class,
            StockMovementSeeder::class, 
        ]);
        
        $this->command->info('Database seeded successfully!');
        $this->command->info('Created:');
        $this->command->info('- ' . Branch::count() . ' branches');
        $this->command->info('- ' . ProductCategory::count() . ' product categories');
        $this->command->info('- ' . Product::count() . ' products');
        $this->command->info('- ' . User::count() . ' users');
        $this->command->info('- ' . Stock::count() . ' stock records');
        $this->command->info('- ' . StockMovement::count() . ' stock movement records');
        
        // Show some statistics
        $lowStockCount = Stock::whereRaw('quantity <= minimum_threshold')->count();
        $this->command->info('- ' . $lowStockCount . ' items currently below minimum threshold');
        
        $totalStockValue = Stock::join('products', 'stocks.product_id', '=', 'products.id')
                                ->sum(\DB::raw('stocks.quantity * products.cost_price'));
        $this->command->info('- Total stock value: RM ' . number_format($totalStockValue, 2));
    }
}
