<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\User;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Order;
use App\Models\OrderItem;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            BranchSeeder::class,           // branches
            UserSeeder::class,             // users (needs branch_id)
            ProductCategorySeeder::class,  // product categories
            ProductSeeder::class,          // products
            StockSeeder::class,            // stocks
            OrderSeeder::class,            // orders
            OrderItemSeeder::class,        // order_items
            StockMovementSeeder::class,    // stock movements
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('📊 Created Records:');
        $this->command->info('- ' . Branch::count() . ' branches');
        $this->command->info('- ' . User::count() . ' users');
        $this->command->info('- ' . ProductCategory::count() . ' product categories');
        $this->command->info('- ' . Product::count() . ' products');
        $this->command->info('- ' . Stock::count() . ' stock records');
        $this->command->info('- ' . StockMovement::count() . ' stock movement records');
        $this->command->info('- ' . Order::count() . ' orders');
        $this->command->info('- ' . OrderItem::count() . ' order items');

        // Show some statistics
        $lowStockCount = Stock::whereRaw('quantity <= minimum_threshold')->count();
        $this->command->info('- ' . $lowStockCount . ' items currently below minimum threshold');

        $totalStockValue = Stock::join('products', 'stocks.product_id', '=', 'products.id')
            ->sum(\DB::raw('stocks.quantity * products.cost_price'));
        $this->command->info('- Total stock value: RM ' . number_format($totalStockValue, 2));
    }
}
