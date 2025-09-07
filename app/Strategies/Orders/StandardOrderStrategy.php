<?php

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
}
