<?php
// Author: Lee Kai Yi
namespace App\Strategies\Orders;

use App\Models\Order;

class UrgentOrderStrategy implements OrderProcessingStrategy
{
    public function processOrder(Order $order): bool
    {
        $order->status = 'pending';
        $order->priority = 'urgent';
        $order->sla_due_at = now()->addHours(4);   // SLA: 4 hours to auto-approve
        $order->save();
        return true;
    }

    public function calculateShippingTime(Order $order): int
    {
        return 1; // Next-day shipping
    }

    public function getPriority(): string
    {
        return 'urgent';
    }

    public function canShip(Order $order): bool
    {
        // Urgent orders are allowed to ship if approved.
        return $order->status === 'approved';
    }
}
