<?php

namespace App\Observers;

use SplObserver;
use SplSubject;
use App\Models\Stock;
use App\Models\User;
use App\Models\Notification;

class LowStockObserver implements SplObserver
{
    public function update(SplSubject $subject)
    {
        if ($subject instanceof Stock && $subject->isLowStock()) {
            $this->notifyStockManagers($subject);
            $this->suggestReorder($subject);
        }
    }

    private function notifyStockManagers(Stock $stock)
    {
        $managers = User::where('role', 'stock_manager')
                        ->where('branch_id', $stock->branch_id)
                        ->get();

        foreach ($managers as $manager) {
            Notification::create([
                'user_id' => $manager->id,
                'title'   => 'Low Stock Alert',
                'message' => "Stock for {$stock->product->name} is running low ({$stock->quantity} remaining)",
                'type'    => 'low_stock'
            ]);
        }
    }

    private function suggestReorder(Stock $stock)
    {
        if (!$stock->branch->is_main) {
            $suggestion = [
                'product_id'          => $stock->product_id,
                'current_stock'       => $stock->quantity,
                'suggested_order_qty' => $stock->minimum_threshold * 2,
                'priority'            => $stock->quantity <= 5 ? 'urgent' : 'standard'
            ];

            cache()->put(
                "reorder_suggestion_{$stock->branch_id}_{$stock->product_id}", 
                $suggestion, 
                now()->addDays(7)
            );
        }
    }
}
