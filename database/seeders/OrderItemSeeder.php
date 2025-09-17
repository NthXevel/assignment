<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;

class OrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();
        $products = Product::all();

        if ($orders->isEmpty() || $products->isEmpty()) {
            $this->command->warn('No orders or products found. Run OrderSeeder and ProductSeeder first.');
            return;
        }

        foreach ($orders as $order) {
            // Each order will have between 1–5 items
            $itemsCount = rand(1, 5);

            $selected = $products->shuffle()->take(min($itemsCount, $products->count()))->values();

            $sum = 0;

            foreach ($selected as $product) {
                /** @var \App\Models\Product $product */
                $qty        = rand(1, 5);
                $unit       = (float)$product->selling_price;
                $lineTotal  = $unit * $qty;
                $sum       += $lineTotal;

                OrderItem::create([
                    'order_id'    => $order->id,
                    'product_id'  => $product->id,
                    'quantity'    => $qty,
                    'unit_price'  => $unit,
                    'total_price' => $lineTotal,
                ]);
            }

            // update order totals once
            $order->update(['total_amount' => $sum]);
        }
    }
}
