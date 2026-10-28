<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRejected extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pedido #{$this->order->id}: comprobante rechazado",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.payment-rejected',
            with: [
                'order' => $this->order,
                'reason' => $this->reason,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
