<?php

namespace App\Strategies\Orders;

use App\Models\Order;

class UrgentOrderStrategy implements OrderProcessingStrategy
{
    public function processOrder(Order $order): bool
    {
        if ($order->creator->hasPermission('approve_urgent_orders')) {
            $order->approve();
        } else {
            $order->status = 'pending';
            $order->priority = 'urgent';
        }

        $order->save();
        return true;
    }

    public function calculateShippingTime(Order $order): int
    {
        return 1; // 1 day
    }

    public function getPriority(): string
    {
        return 'urgent';
    }
}
