<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDeleteStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_paid_order_returns_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create([
            'customer_id' => Customer::factory()->create(),
            'status' => 'paid',
            'expires_at' => null,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->assertSame(7, (int) $product->fresh()->stock);

        $order->delete();

        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_deleting_a_shipped_order_returns_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $order = Order::factory()->create([
            'customer_id' => Customer::factory()->create(),
            'status' => 'shipped',
            'expires_at' => null,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertSame(3, (int) $product->fresh()->stock);

        $order->delete();

        $this->assertSame(5, (int) $product->fresh()->stock);
    }

    public function test_deleting_a_pending_order_does_not_move_the_physical_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->pending()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $this->assertSame(10, (int) $product->fresh()->stock);

        $order->delete();

        $this->assertSame(10, (int) $product->fresh()->stock);
    }
}
