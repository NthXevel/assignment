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
        $subset = $stocks->count() > 50 ? $stocks->random(50) : $stocks;
        
        foreach ($subset as $stock) {
            $this->historyThatEndsAtCurrent($stock);
        }
    }
    
    private function historyThatEndsAtCurrent(Stock $stock): void
    {
        $target = (int)$stock->quantity; // where we must end
        $steps  = rand(3, 6);

        $changes = [];
        for ($i=0; $i<$steps-1; $i++) {
            $changes[] = rand(-25, 50); // sales/restocks
        }
        $sumSoFar     = array_sum($changes);
        $changes[]    = $target - $sumSoFar;  // reconciler

        $balance = 0;
        $start   = now()->subDays(rand(20, 90));

        foreach ($changes as $delta) {
            $balance += $delta;
            $ts = $start->copy()->addDays(rand(1, 10));
            StockMovement::create([
                'stock_id'        => $stock->id,
                'quantity_change' => $delta,
                'reason'          => $delta >= 0 ? 'Stock in' : 'Stock out',
                'balance_after'   => $balance,
                'created_at'      => $ts,
                'updated_at'      => $ts,
            ]);
            $start = $ts;
        }
    }

    /* private function createHistoricalMovements(Stock $stock)
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
    } */
}

