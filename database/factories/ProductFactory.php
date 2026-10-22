<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => Str::title(fake()->unique()->words(3, true)),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 999),
            'stock' => fake()->numberBetween(0, 50),
            'min_stock' => 5,
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    public function withoutCategory(): static
    {
        return $this->state(fn () => ['category_id' => null]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['stock' => 2, 'min_stock' => 5]);
    }
}
