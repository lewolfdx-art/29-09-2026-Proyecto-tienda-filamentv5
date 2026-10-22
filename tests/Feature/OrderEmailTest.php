<?php

namespace Tests\Feature;

use App\Mail\OrderPlaced;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_order_placed_email_after_checkout(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create(['email' => 'comprador@example.com']);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($customer, 'customer')
            ->withSession(['cart' => [$product->id => 1]])
            ->post(route('checkout.store'), [
                'phone' => '+51 987 654 321',
                'address' => 'Av. Siempre Viva 742',
                'city' => 'Lima',
            ]);

        $response->assertRedirect();
        Mail::assertQueued(OrderPlaced::class, function (OrderPlaced $mail) {
            return $mail->hasTo('comprador@example.com');
        });
    }
}
