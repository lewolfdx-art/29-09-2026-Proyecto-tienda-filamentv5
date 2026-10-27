<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_audit_log_page_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('filament.admin.resources.activity-logs.index'))
            ->assertOk()
            ->assertSee('Auditoría');
    }

    public function test_creating_and_updating_a_product_is_logged(): void
    {
        $product = Product::factory()->create(['name' => 'Camiseta']);

        $created = $this->activityFor($product, 'created');

        $this->assertNotNull($created);
        $this->assertSame('products', $created->log_name);
        $this->assertStringContainsString('producto', $created->description);

        $product->update(['name' => 'Polo']);

        $updated = $this->activityFor($product, 'updated');

        $this->assertNotNull($updated);
        $this->assertSame('Camiseta', $updated->properties['old']['name']);
        $this->assertSame('Polo', $updated->properties['attributes']['name']);
    }

    public function test_soft_deleting_a_product_is_logged(): void
    {
        $product = Product::factory()->create();

        $product->delete();

        $deleted = $this->activityFor($product, 'deleted');

        $this->assertNotNull($deleted);
        $this->assertTrue($product->fresh()->trashed());
    }

    public function test_customer_sensitive_fields_are_not_logged(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '+51 999 888 777',
            'address' => 'Av. Siempre Viva 123',
        ]);

        $created = $this->activityFor($customer, 'created');

        $this->assertNotNull($created);
        $this->assertArrayNotHasKey('phone', $created->properties['attributes']);
        $this->assertArrayNotHasKey('address', $created->properties['attributes']);
        $this->assertArrayNotHasKey('password', $created->properties['attributes']);
    }

    public function test_order_contact_fields_are_not_logged(): void
    {
        $order = Order::factory()->create([
            'phone' => '+51 999 888 777',
            'address' => 'Av. Siempre Viva 123',
        ]);

        $created = $this->activityFor($order, 'created');

        $this->assertNotNull($created);
        $this->assertArrayNotHasKey('phone', $created->properties['attributes']);
        $this->assertArrayNotHasKey('address', $created->properties['attributes']);
        $this->assertArrayHasKey('status', $created->properties['attributes']);
    }

    private function activityFor(object $subject, string $event): ?Activity
    {
        return Activity::query()
            ->where('subject_type', $subject::class)
            ->where('subject_id', $subject->getKey())
            ->where('event', $event)
            ->latest('id')
            ->first();
    }
}
