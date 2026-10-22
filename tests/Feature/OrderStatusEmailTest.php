<?php

namespace Tests\Feature;

use App\Mail\OrderStatusUpdated;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderStatusEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_a_status_email_when_order_status_changes(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create(['email' => 'cliente@example.com']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'expires_at' => null,
        ]);

        $order->update(['status' => 'paid']);

        Mail::assertQueued(OrderStatusUpdated::class, function (OrderStatusUpdated $mail) use ($order) {
            return $mail->hasTo('cliente@example.com')
                && $mail->order->id === $order->id
                && $mail->previousStatus === 'pending';
        });
    }

    public function test_it_does_not_queue_a_status_email_when_status_does_not_change(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create(['email' => 'cliente@example.com']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'expires_at' => null,
        ]);

        $order->update(['notes' => 'nota']);

        Mail::assertNotQueued(OrderStatusUpdated::class);
    }
}
