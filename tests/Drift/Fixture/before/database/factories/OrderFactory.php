<?php

namespace Acme\Shop\Database\Factories;

use Acme\Shop\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'number' => fake()->uuid(),
            'note' => null,
            'total' => fake()->numberBetween(100, 100000)
        ];
    }

    public function large(): static
    {
        return $this->state(function (array $attributes) {
            return ['total' => 500000];
        });
    }
}
