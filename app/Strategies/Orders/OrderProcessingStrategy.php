<?php

namespace App\Strategies\Orders;

use App\Models\Order;

interface OrderProcessingStrategy
{
    public function processOrder(Order $order): bool;
    public function calculateShippingTime(Order $order): int;
    public function getPriority(): string;
    public function canShip(Order $order): bool;
}
