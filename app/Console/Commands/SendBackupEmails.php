<?php

namespace App\Console\Commands;

use App\Jobs\SendBackupEmailJob;
use App\Models\EmailRecipient;
use App\Support\DatabaseBackupZip;
use App\Support\InternalNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Throwable;

class SendBackupEmails extends Command
{
    protected $signature = 'app:send-backup-emails';

    protected $description = 'Envía por correo un backup cifrado de la base de datos a los destinatarios que correspondan';

    public function handle(DatabaseBackupZip $backup): int
    {
        $this->cleanupOldDownloads();

        $activeRecipients = EmailRecipient::query()
            ->active()
            ->ofType(EmailRecipient::TYPE_BACKUP_ATTACHMENT)
            ->get();

        $fallbackEmail = null;

        if ($activeRecipients->isEmpty()) {
            Log::warning('No hay destinatarios activos para el aviso de backup adjunto.');

            $fallbackEmail = trim((string) config('notifications.fallback_email'));

            if ($fallbackEmail === '' || filter_var($fallbackEmail, FILTER_VALIDATE_EMAIL) === false) {
                Log::warning('No se envió el backup por correo porque tampoco hay un correo de respaldo válido.');

                return self::SUCCESS;
            }

            $frequency = max(1, (int) config('notifications.backup_fallback_frequency_days', 14));
            $lastSent = Cache::get(SendBackupEmailJob::FALLBACK_CACHE_KEY);

            if ($lastSent && Carbon::parse($lastSent)->gt(now()->subDays($frequency))) {
                $this->info('El correo de respaldo todavía no cumple su frecuencia de backup.');

                return self::SUCCESS;
            }

            $recipients = collect();
        } else {
            $recipients = $activeRecipients
                ->filter(fn (EmailRecipient $recipient): bool => $recipient->isDueForBackup())
                ->values();

            if ($recipients->isEmpty()) {
                $this->info('Ningún destinatario cumple su frecuencia de backup hoy.');

                return self::SUCCESS;
            }
        }

        try {
            $archivePath = $backup->create();
        } catch (Throwable $exception) {
            Log::error('No se pudo generar el backup para enviarlo por correo.', [
                'mensaje' => $exception->getMessage(),
            ]);

            InternalNotifier::backupFailed('No se pudo generar el backup para enviarlo por correo: '.$exception->getMessage());

            $this->error('No se pudo generar el backup: '.$exception->getMessage());

            return self::FAILURE;
        }

        $sizeKb = (int) ceil(File::size($archivePath) / 1024);
        $maxKb = max(1, (int) config('notifications.backup_attachment_max_kb', 15360));

        if ($sizeKb > $maxKb) {
            $filename = basename($archivePath);
            $storedPath = 'backup-emails/'.$filename;

            Storage::disk('local')->put($storedPath, File::get($archivePath));
            File::delete($archivePath);

            $url = URL::temporarySignedRoute(
                'admin.backup-email.download',
                now()->addDays(max(1, (int) config('notifications.backup_link_ttl_days', 7))),
                ['file' => $filename],
            );

            SendBackupEmailJob::dispatch(
                $recipients->pluck('id')->all(),
                null,
                $url,
                $fallbackEmail,
                false,
            );

            $this->info("El backup supera {$maxKb} KB; se enviará un enlace de descarga autenticado.");

            return self::SUCCESS;
        }

        SendBackupEmailJob::dispatch(
            $recipients->pluck('id')->all(),
            $archivePath,
            null,
            $fallbackEmail,
            true,
        );

        $this->info('Backup enviado a la cola para su entrega por correo.');

        return self::SUCCESS;
    }

    private function cleanupOldDownloads(): void
    {
        $disk = Storage::disk('local');
        $expiration = now()->subDays(max(1, (int) config('notifications.backup_link_ttl_days', 7)))->timestamp;

        foreach ($disk->files('backup-emails') as $file) {
            if ($disk->lastModified($file) < $expiration) {
                $disk->delete($file);
            }
        }
    }
}
