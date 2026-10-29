<?php

namespace Tests\Feature;

use App\Mail\InternalNotice;
use App\Models\EmailRecipient;
use App\Models\User;
use App\Support\InternalNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InternalNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_a_notice_to_active_recipients_of_the_type(): void
    {
        Mail::fake();

        EmailRecipient::factory()->create([
            'email' => 'pedidos@example.com',
            'tipos' => [EmailRecipient::TYPE_NEW_ORDER],
            'activo' => true,
        ]);

        EmailRecipient::factory()->create([
            'email' => 'inactivo@example.com',
            'tipos' => [EmailRecipient::TYPE_NEW_ORDER],
            'activo' => false,
        ]);

        EmailRecipient::factory()->create([
            'email' => 'otro-tipo@example.com',
            'tipos' => [EmailRecipient::TYPE_LOW_STOCK],
            'activo' => true,
        ]);

        InternalNotifier::send(
            EmailRecipient::TYPE_NEW_ORDER,
            'Nuevo pedido',
            'Pedido #100',
            'https://example.com/admin/orders/100',
            'Ver pedido',
        );

        Mail::assertQueued(InternalNotice::class, function (InternalNotice $mail): bool {
            return $mail->hasTo('pedidos@example.com')
                && $mail->noticeSubject === 'Nuevo pedido'
                && $mail->bodyText === 'Pedido #100'
                && $mail->actionUrl === 'https://example.com/admin/orders/100'
                && $mail->actionLabel === 'Ver pedido';
        });

        Mail::assertNotQueued(InternalNotice::class, fn (InternalNotice $mail): bool => $mail->hasTo('inactivo@example.com'));
        Mail::assertNotQueued(InternalNotice::class, fn (InternalNotice $mail): bool => $mail->hasTo('otro-tipo@example.com'));
    }

    public function test_it_uses_the_user_email_instead_of_the_stored_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'real@example.com']);

        EmailRecipient::factory()->create([
            'user_id' => $user->id,
            'email' => 'obsoleto@example.com',
            'tipos' => [EmailRecipient::TYPE_PAYMENT_PROOF],
        ]);

        InternalNotifier::send(
            EmailRecipient::TYPE_PAYMENT_PROOF,
            'Comprobante',
            'Comprobante recibido',
        );

        Mail::assertQueued(
            InternalNotice::class,
            fn (InternalNotice $mail): bool => $mail->hasTo('real@example.com'),
        );
    }

    public function test_it_uses_the_fallback_and_logs_a_warning_when_there_are_no_recipients(): void
    {
        Mail::fake();
        Log::spy();

        config(['notifications.fallback_email' => 'respaldo@example.com']);

        InternalNotifier::send(
            EmailRecipient::TYPE_BACKUP_FAILURE,
            'Falla de backup',
            'El backup falló',
        );

        Mail::assertQueued(
            InternalNotice::class,
            fn (InternalNotice $mail): bool => $mail->hasTo('respaldo@example.com'),
        );

        Log::shouldHaveReceived('warning')->atLeast()->once();
    }

    public function test_it_only_logs_when_there_are_no_recipients_nor_valid_fallback(): void
    {
        Mail::fake();
        Log::spy();

        config(['notifications.fallback_email' => '']);

        InternalNotifier::send(
            EmailRecipient::TYPE_LOW_STOCK,
            'Stock bajo',
            'Sin destinatarios',
        );

        Mail::assertNothingQueued();
        Log::shouldHaveReceived('warning')->atLeast()->once();
    }
}
