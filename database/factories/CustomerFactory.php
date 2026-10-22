<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+51 ### ### ###'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'password' => 'password',
        ];
    }

    /**
     * Cliente sin datos de contacto (registrado rápido).
     */
    public function withoutContactInfo(): static
    {
        return $this->state(fn () => [
            'phone' => null,
            'address' => null,
            'city' => null,
        ]);
    }
}
