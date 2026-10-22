<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_command_expires_pending_orders_whose_reservation_lapsed(): void
    {
        $order = Order::factory()->expired()->create();

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame('expired', $order->fresh()->status);
    }

    public function test_the_command_leaves_valid_pending_orders_alone(): void
    {
        $order = Order::factory()->pending()->create();

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_the_command_leaves_paid_orders_alone(): void
    {
        $order = Order::factory()->paid()->create();

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_the_command_leaves_pending_orders_without_expiry_alone(): void
    {
        $order = Order::factory()->pending()->create(['expires_at' => null]);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_the_dry_run_option_changes_nothing(): void
    {
        $order = Order::factory()->expired()->create();

        $this->artisan('orders:expire --dry-run')->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_the_command_releases_the_reserved_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->expired()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        // La reserva ya no bloqueaba el stock desde antes: el filtro de
        // productos ignora los pedidos vencidos.
        $this->assertSame(10, (int) $product->fresh()->available_stock);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame('expired', $order->fresh()->status);
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(10, (int) $product->fresh()->available_stock);
    }

    public function test_expiring_a_pending_order_does_not_move_the_physical_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->expired()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $this->artisan('orders:expire')->assertSuccessful();

        // Un pedido pendiente nunca consumió stock, así que expirarlo
        // no debe devolverlo.
        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_the_customer_sees_the_expired_order_as_expired(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->expired()->create(['customer_id' => $customer->id]);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->actingAs($customer, 'customer')
            ->get(route('orders.show', $order->id))
            ->assertOk()
            ->assertSee('Expirado')
            ->assertSee('Este pedido expiró');
    }

    public function test_the_command_is_scheduled(): void
    {
        $schedule = app(Schedule::class);

        $events = collect($schedule->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'orders:expire'));

        $this->assertCount(1, $events);
        $this->assertSame('*/5 * * * *', $events->first()->expression);
    }
}
