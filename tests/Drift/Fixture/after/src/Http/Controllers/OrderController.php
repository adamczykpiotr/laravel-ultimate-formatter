<?php

declare(strict_types=1);

namespace Acme\Shop\Http\Controllers;

use Acme\Shop\Models\Order;
use Acme\Shop\Services\OrderService;

class OrderController
{
    /**
     * @param OrderService $orders
     */
    public function __construct(private OrderService $orders) {}

    /**
     * @param Order $order
     *
     * @return string
     */
    public function show(Order $order): string
    {
        return $this->orders->summary($order, null);
    }
}
