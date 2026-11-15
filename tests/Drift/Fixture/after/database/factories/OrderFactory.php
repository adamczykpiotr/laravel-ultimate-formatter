<?php

declare(strict_types=1);

namespace Acme\Shop\Database\Factories;

use Acme\Shop\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return mixed[]
     */
    public function definition(): array
    {
        return [
            Order::ATTRIBUTE_NUMBER => fake()->uuid(),
            Order::ATTRIBUTE_NOTE => null,
            Order::ATTRIBUTE_TOTAL => fake()->numberBetween(100, 100_000),
        ];
    }

    /**
     * @return static
     */
    public function large(): static
    {
        return $this->state(fn(array $attributes): array => [Order::ATTRIBUTE_TOTAL => 500_000]);
    }
}
