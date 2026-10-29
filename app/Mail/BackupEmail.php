<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $noticeSubject,
        public string $bodyText,
        public ?string $attachmentPath = null,
        public ?string $attachmentName = null,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->noticeSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.internal-notice',
            with: [
                'noticeSubject' => $this->noticeSubject,
                'bodyText' => $this->bodyText,
                'actionUrl' => $this->actionUrl,
                'actionLabel' => $this->actionLabel,
            ],
        );
    }

    public function attachments(): array
    {
        if (! $this->attachmentPath) {
            return [];
        }

        return [
            Attachment::fromPath($this->attachmentPath)
                ->as($this->attachmentName ?: 'backup-base-de-datos.zip')
                ->withMime('application/zip'),
        ];
    }
}
