<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReservationTest extends TestCase
{
    use RefreshDatabase;

    private function orderWith(Product $product, int $quantity = 2, string $status = 'pending'): Order
    {
        $order = Order::factory()->create([
            'customer_id' => Customer::factory()->create(),
            'status' => $status,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        return $order;
    }

    public function test_a_pending_order_reserves_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->orderWith($product, 3);

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(7, (int) $product->fresh()->available_stock);
    }

    public function test_paying_an_order_discounts_the_physical_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 3);

        $order->update(['status' => 'paid']);

        $this->assertSame(7, (int) $product->fresh()->stock);
        $this->assertSame(7, (int) $product->fresh()->available_stock);
    }

    public function test_cancelling_a_paid_order_returns_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 3, 'paid');

        $order->update(['status' => 'cancelled']);

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(10, (int) $product->fresh()->available_stock);
    }

    public function test_an_expired_reservation_does_not_block_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 3);

        $order->update(['expires_at' => now()->subMinute()]);

        $this->assertSame(10, (int) $product->fresh()->available_stock);
    }

    public function test_an_order_without_expiry_keeps_reserving_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 3);

        $order->update(['expires_at' => null]);

        $this->assertSame(7, (int) $product->fresh()->available_stock);
    }

    public function test_the_order_total_is_recalculated_when_items_change(): void
    {
        $product = Product::factory()->create(['price' => 20]);
        $order = $this->orderWith($product, 2);

        $this->assertEqualsWithDelta(40.00, (float) $order->fresh()->total, 0.001);

        $order->items()->first()->update(['quantity' => 3]);

        $this->assertEqualsWithDelta(60.00, (float) $order->fresh()->total, 0.001);
    }

    public function test_the_item_price_comes_from_the_product(): void
    {
        $product = Product::factory()->create(['price' => 33.75]);
        $order = $this->orderWith($product, 1);

        $this->assertEqualsWithDelta(33.75, (float) $order->items()->first()->unit_price, 0.001);
    }

    public function test_a_customer_cannot_see_another_customers_order(): void
    {
        $order = $this->orderWith(Product::factory()->create());

        $this->actingAs(Customer::factory()->create(), 'customer')
            ->get(route('orders.show', $order->id))
            ->assertNotFound();
    }

    public function test_an_order_created_as_paid_discounts_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create(['status' => 'paid']);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->assertSame(7, (int) $product->fresh()->stock);
    }

    public function test_adding_an_item_to_a_paid_order_discounts_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 2, 'paid');

        $this->assertSame(8, (int) $product->fresh()->stock);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $this->assertSame(4, (int) $product->fresh()->stock);
    }

    public function test_adding_an_item_to_a_pending_order_only_reserves_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 2);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(4, (int) $product->fresh()->available_stock);
    }

    public function test_removing_an_item_from_a_paid_order_returns_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 3, 'paid');

        $this->assertSame(7, (int) $product->fresh()->stock);

        $order->items()->first()->delete();

        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_changing_the_quantity_of_an_item_of_a_paid_order_adjusts_the_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = $this->orderWith($product, 3, 'paid');

        $this->assertSame(7, (int) $product->fresh()->stock);

        $order->items()->first()->update(['quantity' => 5]);

        $this->assertSame(5, (int) $product->fresh()->stock);
    }

    public function test_the_stock_never_goes_below_zero(): void
    {
        $product = Product::factory()->create(['stock' => 2]);
        $order = $this->orderWith($product, 5, 'paid');

        $this->assertSame(0, (int) $product->fresh()->stock);
    }
}
