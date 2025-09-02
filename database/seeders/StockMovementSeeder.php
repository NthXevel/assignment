<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Stock;
use App\Models\StockMovement;

class StockMovementSeeder extends Seeder
{
    public function run()
    {
        // Create some historical stock movements for demonstration
        $stocks = Stock::all();
        
        foreach ($stocks->random(min(50, $stocks->count())) as $stock) {
            $this->createHistoricalMovements($stock);
        }
    }
    
    private function createHistoricalMovements(Stock $stock)
    {
        $movements = [
            [
                'quantity_change' => rand(20, 100),
                'reason' => 'Initial stock intake',
                'created_at' => now()->subDays(rand(30, 90)),
            ],
            [
                'quantity_change' => -rand(5, 25),
                'reason' => 'Order fulfilled: ORD-2024-' . str_pad(rand(1, 999), 6, '0', STR_PAD_LEFT),
                'created_at' => now()->subDays(rand(15, 45)),
            ],
            [
                'quantity_change' => rand(10, 50),
                'reason' => 'Stock replenishment',
                'created_at' => now()->subDays(rand(5, 20)),
            ],
        ];
        
        $runningBalance = $stock->quantity;
        
        // Create movements in reverse chronological order to maintain balance
        foreach (array_reverse($movements) as $movement) {
            $runningBalance -= $movement['quantity_change'];
            
            StockMovement::create([
                'stock_id' => $stock->id,
                'quantity_change' => $movement['quantity_change'],
                'reason' => $movement['reason'],
                'balance_after' => $runningBalance + $movement['quantity_change'],
                'created_at' => $movement['created_at'],
                'updated_at' => $movement['created_at'],
            ]);
        }
    }
}

