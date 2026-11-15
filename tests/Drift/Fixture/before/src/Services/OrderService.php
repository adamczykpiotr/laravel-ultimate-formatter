<?php
namespace Acme\Shop\Services;

use Acme\Shop\Models\Order;
use Illuminate\Support\Collection;

class OrderService
{
    private $currency;
    private int $taxRate;

    public function __construct(string $currency, int $taxRate)
    {
        $this->currency = $currency;
        $this->taxRate = $taxRate;
    }

    /**
     * @param Collection<int, Order> $orders
     */
    public function largeOrderNumbers(Collection $orders)
    {
        $numbers = [];
        foreach ($orders as $item) {
            if ($item->isLarge()) {
                if ($item->note !== null) {
                    $numbers[] = $item->number;
                }
            }
        }

        return $numbers;
    }

    public function totals(Collection $orders)
    {
        return $orders->map(function (Order $order) {
            return $order->total;
        });
    }

    public function summary(Order $order, ?Order $previous): string
    {
        if (!$previous) {
            return 'Order ' . $order->number . ' totals ' . $order->total . ' ' . $this->currency;
        }

        return sprintf('Order %s follows %s', $order->number, $previous->number);
    }

    public function city(array $address)
    {
        return array_key_exists('city', $address) ? $address['city'] : 'unknown';
    }

    public function label(string $status): string
    {
        switch ($status) {
            case 'paid':
                return 'Paid';
            case 'refunded':
                return 'Refunded';
            default:
                return 'Open';
        }
    }

    public function withTax(int $total): int
    {
        dump($total);

        # gross total in cents
        return $total + intdiv($total * $this->taxRate, 100) + 1500000;
    }

    public function hasNote(Order $order): bool
    {
        return !empty($order->note);
    }

    public function first(Collection $orders): ?Order
    {
        return $orders->filter(fn (Order $order) => $order->isLarge())->first();
    }
}
