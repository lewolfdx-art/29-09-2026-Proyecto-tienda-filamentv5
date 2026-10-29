<?php

namespace App\Jobs;

use App\Mail\BackupEmail;
use App\Models\EmailRecipient;
use App\Support\InternalNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBackupEmailJob implements ShouldQueue
{
    use Queueable;

    public const FALLBACK_CACHE_KEY = 'backup-email-fallback-last-sent';

    /**
     * @param  array<int, int>  $recipientIds
     */
    public function __construct(
        public array $recipientIds,
        public ?string $attachmentPath = null,
        public ?string $downloadUrl = null,
        public ?string $fallbackEmail = null,
        public bool $deleteAttachmentAfterSend = true,
    ) {}

    public function handle(): void
    {
        $subject = 'Backup de la base de datos — '.now()->format('d/m/Y');
        $limitMb = round(((int) config('notifications.backup_attachment_max_kb', 15360)) / 1024, 1);

        $body = $this->attachmentPath
            ? "Adjuntamos el respaldo de la base de datos generado el {$this->dateLabel()}.\n\n"
                ."El archivo está cifrado con AES-256 y la contraseña configurada en BACKUP_ARCHIVE_PASSWORD. "
                .'Por seguridad, la contraseña no se incluye en este correo.'
            : "El respaldo de la base de datos generado el {$this->dateLabel()} supera el límite de {$limitMb} MB, "
                ."por eso no se adjunta.\n\nPuedes descargarlo desde el panel de administración con el siguiente enlace. "
                .'El archivo está cifrado con AES-256 y la contraseña configurada en BACKUP_ARCHIVE_PASSWORD.';

        $recipients = EmailRecipient::query()
            ->whereKey($this->recipientIds)
            ->active()
            ->ofType(EmailRecipient::TYPE_BACKUP_ATTACHMENT)
            ->get();

        $emails = $recipients
            ->map(fn (EmailRecipient $recipient): string => $recipient->effectiveEmail())
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values();

        if (filled($this->fallbackEmail) && filter_var($this->fallbackEmail, FILTER_VALIDATE_EMAIL) !== false) {
            $emails->push((string) $this->fallbackEmail);
        }

        $emails = $emails
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->unique()
            ->values();

        $failures = 0;

        try {
            foreach ($emails as $email) {
                try {
                    Mail::to($email)->send(new BackupEmail(
                        $subject,
                        $body,
                        $this->attachmentPath,
                        $this->attachmentPath ? basename($this->attachmentPath) : null,
                        $this->downloadUrl,
                        $this->downloadUrl ? 'Descargar backup' : null,
                    ));

                    $recipient = $recipients->first(
                        fn (EmailRecipient $item): bool => mb_strtolower($item->effectiveEmail()) === $email,
                    );

                    if ($recipient) {
                        $recipient->forceFill(['last_backup_sent_at' => now()])->saveQuietly();
                    } else {
                        Cache::forever(self::FALLBACK_CACHE_KEY, now()->toIso8601String());
                    }
                } catch (Throwable $exception) {
                    $failures++;
                    Log::error('No se pudo enviar el backup por correo.', [
                        'correo' => $email,
                        'mensaje' => $exception->getMessage(),
                    ]);
                }
            }
        } finally {
            if ($this->deleteAttachmentAfterSend && $this->attachmentPath && File::exists($this->attachmentPath)) {
                File::delete($this->attachmentPath);
            }
        }

        if ($failures > 0) {
            InternalNotifier::backupFailed("No se pudo enviar el backup adjunto a {$failures} destinatario(s). Revisa los logs.");
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falló el envío del backup por correo.', [
            'mensaje' => $exception->getMessage(),
        ]);

        InternalNotifier::backupFailed('Falló el envío del backup por correo: '.$exception->getMessage());
    }

    private function dateLabel(): string
    {
        return now()->format('d/m/Y H:i');
    }
}
