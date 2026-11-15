<?php

declare(strict_types=1);

namespace Acme\Shop\Services;

use Acme\Shop\Models\Order;
use Illuminate\Support\Collection;

class OrderService
{
    /**
     * @param string $currency
     * @param int $taxRate
     */
    public function __construct(private string $currency, private int $taxRate) {}

    /**
     * @param Collection<int, Order> $orders
     *
     * @return mixed[]
     */
    public function largeOrderNumbers(Collection $orders): array
    {
        $numbers = [];
        foreach ($orders as $order) {
            if ($order->isLarge() === false) {
                continue;
            }
            if ($order->note === null) {
                continue;
            }
            $numbers[] = $order->number;
        }

        return $numbers;
    }

    /**
     * @param Collection $orders
     */
    public function totals(Collection $orders)
    {
        return $orders->map(fn(Order $order) => $order->total);
    }

    /**
     * @param Order $order
     * @param Order|null $previous
     *
     * @return string
     */
    public function summary(Order $order, ?Order $previous): string
    {
        if ($previous === null) {
            return "Order {$order->number} totals {$order->total} {$this->currency}";
        }

        return sprintf('Order %s follows %s', $order->number, $previous->number);
    }

    /**
     * @param array<string, mixed> $address
     */
    public function city(array $address)
    {
        return $address['city'] ?? 'unknown';
    }

    /**
     * @param string $status
     *
     * @return string
     */
    public function label(string $status): string
    {
        return match ($status) {
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            default => 'Open',
        };
    }

    /**
     * @param int $total
     *
     * @return int
     */
    public function withTax(int $total): int
    {
        // gross total in cents
        return $total + intdiv($total * $this->taxRate, 100) + 1_500_000;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function hasNote(Order $order): bool
    {
        return empty($order->note) === false;
    }

    /**
     * @param Collection $orders
     *
     * @return Order|null
     */
    public function first(Collection $orders): ?Order
    {
        return $orders->filter(fn (Order $order): bool => $order->isLarge())->first();
    }
}
