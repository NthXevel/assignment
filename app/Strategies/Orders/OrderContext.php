<?php

namespace App\Strategies\Orders;

use App\Models\Order;

class OrderContext
{
    private OrderProcessingStrategy $strategy;

    public function setStrategy(OrderProcessingStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function processOrder(Order $order): bool
    {
        return $this->strategy->processOrder($order);
    }

    public function getShippingTime(Order $order): int
    {
        return $this->strategy->calculateShippingTime($order);
    }
}
