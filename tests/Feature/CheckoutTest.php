<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function checkout(array $payload = [], array $cart = [])
    {
        return $this->actingAs(Customer::factory()->create(), 'customer')
            ->withSession(['cart' => $cart])
            ->post(route('checkout.store'), array_merge([
                'phone' => '+51 987 654 321',
                'address' => 'Av. Siempre Viva 742',
                'city' => 'Lima',
            ], $payload));
    }

    public function test_a_customer_can_place_an_order(): void
    {
        $product = Product::factory()->create(['stock' => 10, 'price' => 25.50]);

        $response = $this->checkout(cart: [$product->id => 2]);

        $order = Order::first();

        $this->assertNotNull($order);
        $response->assertRedirect(route('orders.show', $order->id));

        $this->assertSame('pending', $order->status);
        $this->assertEqualsWithDelta(51.00, (float) $order->total, 0.001);
        $this->assertNotNull($order->expires_at);
        $this->assertTrue($order->expires_at->isFuture());

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 25.50,
        ]);
    }

    public function test_the_order_reserves_the_stock_without_discounting_it(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->checkout(cart: [$product->id => 3]);

        // El stock físico solo baja cuando el pedido se paga.
        $this->assertSame(10, (int) $product->fresh()->stock);

        // Pero ya no se puede vender de nuevo.
        $this->assertSame(7, (int) $product->fresh()->available_stock);
    }

    public function test_the_cart_is_emptied_after_the_order(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->checkout(cart: [$product->id => 2]);

        $this->assertSame([], session('cart', []));
    }

    public function test_it_saves_the_delivery_data_on_the_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->withSession(['cart' => [Product::factory()->create(['stock' => 5])->id => 1]])
            ->post(route('checkout.store'), [
                'phone' => '+51 999 111 222',
                'address' => 'Calle Las Flores 123',
                'city' => 'Arequipa',
            ]);

        $customer->refresh();

        $this->assertSame('+51 999 111 222', $customer->phone);
        $this->assertSame('Calle Las Flores 123', $customer->address);
        $this->assertSame('Arequipa', $customer->city);
    }

    public function test_it_rejects_an_order_when_there_is_not_enough_stock(): void
    {
        $product = Product::factory()->create(['stock' => 1]);

        $response = $this->checkout(cart: [$product->id => 5]);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_it_rejects_an_order_when_the_cart_is_empty(): void
    {
        $response = $this->checkout();

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_rejects_invalid_delivery_data(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $response = $this->checkout(
            payload: ['phone' => 'no-es-un-telefono', 'address' => '', 'city' => ''],
            cart: [$product->id => 1],
        );

        $response->assertSessionHasErrors(['phone', 'address', 'city']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_counts_other_pending_orders_as_reserved(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->pending()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $this->assertSame(6, (int) $product->fresh()->available_stock);

        $response = $this->checkout(cart: [$product->id => 7]);

        $response->assertRedirect(route('cart.index'));
        $this->assertDatabaseCount('orders', 1);
    }
}
