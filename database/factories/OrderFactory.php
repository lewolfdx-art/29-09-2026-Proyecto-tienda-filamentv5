<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
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
            'customer_id' => Customer::factory(),
            'status' => 'pending',
            'total' => 0,
            'notes' => null,
            'expires_at' => now()->addDay(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid', 'expires_at' => null]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled', 'expires_at' => null]);
    }

    /**
     * Pedido pendiente cuya reserva de stock ya venció.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'expires_at' => now()->subMinute(),
        ]);
    }
}
