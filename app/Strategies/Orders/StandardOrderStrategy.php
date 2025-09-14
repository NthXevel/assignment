<?php
// Author: Lee Kai Yi
namespace App\Strategies\Orders;

use App\Models\Order;

class StandardOrderStrategy implements OrderProcessingStrategy
{
    public function processOrder(Order $order): bool
    {
        $order->status = 'pending';
        $order->save();
        return true;
    }

    public function calculateShippingTime(Order $order): int
    {
        return 3; // Estimated 3 days
    }

    public function getPriority(): string
    {
        return 'standard';
    }

    public function canShip(Order $order): bool
    {
        // Block shipping standard orders if there exists any urgent order
        // from the same supplying branch for the same product(s)
        // that is not yet completed (pending or approved but not shipped/received/cancelled).
        $productIds = $order->items()->pluck('product_id')->all();

        if (empty($productIds)) {
            return true;
        }

        $existsBlockingUrgent = Order::where('supplying_branch_id', $order->supplying_branch_id)
            ->where('id', '!=', $order->id)
            ->where('priority', 'urgent')
            ->whereIn('status', ['pending', 'approved'])
            ->whereHas('items', function ($q) use ($productIds) {
                $q->whereIn('product_id', $productIds);
            })
            ->exists();

        return !$existsBlockingUrgent;
    }
}
