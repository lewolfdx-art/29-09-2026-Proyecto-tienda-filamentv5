<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderExpiryAtomicTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_only_affects_pending_orders_with_expired_reservation(): void
    {
        $expired = Order::factory()->expired()->create();
        $paid = Order::factory()->paid()->create();
        $pendingValid = Order::factory()->pending()->create();

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame('paid', $paid->fresh()->status);
        $this->assertSame('pending', $pendingValid->fresh()->status);
    }
}
