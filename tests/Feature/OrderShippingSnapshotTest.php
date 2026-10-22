<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShippingSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_stores_delivery_data_on_the_order(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $response = $this->actingAs($customer, 'customer')
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('checkout.store'), [
                'phone' => '+51 999 111 222',
                'address' => 'Calle Las Flores 123',
                'city' => 'Arequipa',
            ]);

        $response->assertRedirect();

        $order = \App\Models\Order::first();

        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame('+51 999 111 222', $order->phone);
        $this->assertSame('Calle Las Flores 123', $order->address);
        $this->assertSame('Arequipa', $order->city);
    }
}
