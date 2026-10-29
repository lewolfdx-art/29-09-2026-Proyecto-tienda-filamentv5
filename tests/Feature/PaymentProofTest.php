<?php

namespace Tests\Feature;

use App\Mail\OrderStatusUpdated;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(Customer $customer, array $overrides = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total' => 51,
            'payment_method' => 'yape',
            'expires_at' => now()->addDay(),
        ], $overrides));

        $product = Product::factory()->create(['stock' => 10]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 25.50,
        ]);

        return $order->refresh();
    }

    private function proof(string $name = 'yape.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 600, 800);
    }

    public function test_a_customer_can_upload_a_payment_proof(): void
    {
        Storage::fake('local');

        $customer = Customer::factory()->create();
        $order = $this->orderFor($customer);

        $response = $this->actingAs($customer, 'customer')->post(route('orders.payment-proof.store', $order), [
            'payment_method' => 'yape',
            'payment_reference' => 'OP-123456',
            'proof' => $this->proof(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $order->refresh();

        $this->assertSame('yape', $order->payment_method);
        $this->assertSame('OP-123456', $order->payment_reference);
        $this->assertNotNull($order->payment_submitted_at);
        $this->assertNull($order->payment_rejection_reason);
        $this->assertSame('pending', $order->status);
        $this->assertTrue($order->paymentIsUnderReview());
        Storage::disk('local')->assertExists($order->payment_proof_path);
    }

    public function test_a_customer_cannot_upload_a_proof_to_another_customers_order(): void
    {
        Storage::fake('local');

        $owner = Customer::factory()->create();
        $intruder = Customer::factory()->create();
        $order = $this->orderFor($owner);

        $this->actingAs($intruder, 'customer')
            ->post(route('orders.payment-proof.store', $order), [
                'payment_method' => 'yape',
                'payment_reference' => 'OP-999999',
                'proof' => $this->proof(),
            ])
            ->assertNotFound();

        $this->assertNull($order->fresh()->payment_proof_path);
    }

    public function test_a_proof_cannot_be_uploaded_to_a_paid_or_expired_order(): void
    {
        Storage::fake('local');

        $customer = Customer::factory()->create();
        $paid = $this->orderFor($customer, ['status' => 'paid']);
        $expired = $this->orderFor($customer, ['expires_at' => now()->subMinute()]);

        foreach ([$paid, $expired] as $order) {
            $this->actingAs($customer, 'customer')
                ->post(route('orders.payment-proof.store', $order), [
                    'payment_method' => 'yape',
                    'payment_reference' => 'OP-0001',
                    'proof' => $this->proof(),
                ])
                ->assertSessionHas('error');

            $this->assertNull($order->fresh()->payment_proof_path);
        }
    }

    public function test_only_images_and_pdfs_are_accepted_as_proof(): void
    {
        Storage::fake('local');

        $customer = Customer::factory()->create();
        $order = $this->orderFor($customer);

        $this->actingAs($customer, 'customer')
            ->post(route('orders.payment-proof.store', $order), [
                'payment_method' => 'yape',
                'payment_reference' => 'OP-123456',
                'proof' => UploadedFile::fake()->create('comprobante.exe', 10, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('proof');

        $this->assertNull($order->fresh()->payment_proof_path);
    }

    public function test_replacing_a_proof_deletes_the_previous_file_and_clears_a_rejection(): void
    {
        Storage::fake('local');

        $customer = Customer::factory()->create();
        $order = $this->orderFor($customer, [
            'payment_rejection_reason' => 'El monto no coincide.',
        ]);

        $this->actingAs($customer, 'customer')->post(route('orders.payment-proof.store', $order), [
            'payment_method' => 'yape',
            'payment_reference' => 'OP-111111',
            'proof' => $this->proof('primero.jpg'),
        ]);

        $firstPath = $order->fresh()->payment_proof_path;
        Storage::disk('local')->assertExists($firstPath);

        $this->actingAs($customer, 'customer')->post(route('orders.payment-proof.store', $order), [
            'payment_method' => 'plin',
            'payment_reference' => 'OP-222222',
            'proof' => $this->proof('segundo.jpg'),
        ]);

        $order->refresh();

        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($order->payment_proof_path);
        $this->assertSame('plin', $order->payment_method);
        $this->assertSame('OP-222222', $order->payment_reference);
        $this->assertNull($order->payment_rejection_reason);
    }

    public function test_payment_proof_links_are_temporary(): void
    {
        Storage::fake('local');

        $customer = Customer::factory()->create();
        $order = $this->orderFor($customer, [
            'payment_proof_path' => 'payment-proofs/comprobante.jpg',
            'payment_submitted_at' => now(),
        ]);

        Storage::disk('local')->put('payment-proofs/comprobante.jpg', 'contenido');

        $url = $order->paymentProofUrl();

        $this->assertNotNull($url);
        $this->assertStringContainsString('expiration=', $url);
    }

    public function test_approving_payment_records_who_verified_it_and_discounts_stock(): void
    {
        Mail::fake();

        $staff = User::factory()->create();
        $customer = Customer::factory()->create(['email' => 'cliente@example.com']);
        $order = $this->orderFor($customer, [
            'payment_submitted_at' => now(),
            'payment_reference' => 'OP-123456',
        ]);

        $product = $order->items()->first()->product;

        $this->actingAs($staff, 'web');

        $order->update(['status' => 'paid']);

        $order->refresh();

        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->payment_verified_at);
        $this->assertSame($staff->id, $order->payment_verified_by);
        $this->assertSame(8, (int) $product->fresh()->stock);
        Mail::assertQueued(OrderStatusUpdated::class);
    }

    public function test_the_order_page_shows_payment_instructions_and_the_upload_form(): void
    {
        config([
            'payments.methods.yape.phone' => '987654321',
            'payments.methods.yape.holder' => 'Dreven',
        ]);

        $customer = Customer::factory()->create();
        $order = $this->orderFor($customer);

        $this->actingAs($customer, 'customer')
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Paga con Yape')
            ->assertSee('987654321')
            ->assertSee('Subir comprobante de pago');
    }
}
