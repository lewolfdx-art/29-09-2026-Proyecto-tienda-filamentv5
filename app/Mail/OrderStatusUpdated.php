<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $previousStatus)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pedido #{$this->order->id} - Estado: {$this->order->statusLabel()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.status-updated',
            with: [
                'order' => $this->order,
                'previousStatus' => $this->previousStatus,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
