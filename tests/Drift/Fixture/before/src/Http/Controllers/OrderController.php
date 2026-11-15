<?php

namespace Acme\Shop\Http\Controllers;

use Acme\Shop\Services\OrderService;

class OrderController
{
    public function __construct(private OrderService $orders) {}

    public function show(\Acme\Shop\Models\Order $order)
    {
        return $this->orders->summary($order, null);
    }
}
