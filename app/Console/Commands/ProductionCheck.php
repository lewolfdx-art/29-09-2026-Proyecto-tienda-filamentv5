<?php

namespace App\Console\Commands;

use App\Models\EmailRecipient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ProductionCheck extends Command
{
    protected $signature = 'app:production-check';

    protected $description = 'Verifica que la configuración y los archivos necesarios para producción estén listos';

    public function handle(): int
    {
        $checks = [
            ['APP_ENV=production', app()->environment('production'), 'Configura APP_ENV=production en el .env del servidor.'],
            ['APP_DEBUG=false', config('app.debug') === false, 'Desactiva APP_DEBUG para no exponer trazas.'],
            ['APP_KEY definida', filled(config('app.key')), 'Ejecuta php artisan key:generate.'],
            ['APP_URL con HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'Usa https://tu-dominio.com en APP_URL.'],
            ['APP_URL no es local', ! $this->isLocalUrl((string) config('app.url')), 'Sustituye localhost/127.0.0.1 por tu dominio real.'],
            ['Base de datos MySQL', config('database.default') === 'mysql', 'Configura DB_CONNECTION=mysql.'],
            ['Credenciales de BD', filled(config('database.connections.mysql.database'))
                && filled(config('database.connections.mysql.username'))
                && filled(config('database.connections.mysql.password')), 'Completa DB_DATABASE, DB_USERNAME y DB_PASSWORD.'],
            ['Sesiones cifradas', (bool) config('session.encrypt'), 'Activa SESSION_ENCRYPT=true.'],
            ['Cookies solo por HTTPS', config('session.secure') === true, 'Activa SESSION_SECURE_COOKIE=true.'],
            ['Cola no síncrona', config('queue.default') !== 'sync', 'Usa QUEUE_CONNECTION=database y un worker por cron.'],
            ['Correo real configurado', config('mail.default') !== 'log'
                && ! str_ends_with((string) config('mail.from.address'), '@example.com'), 'Configura SMTP real y MAIL_FROM_ADDRESS.'],
            ['Métodos de pago configurados', $this->paymentMethodsConfigured(), 'Completa el teléfono o QR de al menos un método de pago.'],
            ['Contraseña de backups', strlen((string) config('backup.backup.password')) >= 12, 'Define BACKUP_ARCHIVE_PASSWORD (mínimo 12 caracteres).'],
            ['Destinatarios de avisos internos', $this->internalRecipientsConfigured(), $this->internalRecipientsHint()],
            ['Disco de backups', filled(config('backup.backup.destination.disks')), 'Configura BACKUP_DISK (por ejemplo backups o s3).'],
            ['Comprobantes en disco privado', config('payments.proof_disk') !== 'public', 'Usa PAYMENT_PROOF_DISK=local para proteger los comprobantes.'],
            ['Enlace de storage', file_exists(public_path('storage')) || is_link(public_path('storage')), 'Ejecuta php artisan storage:link.'],
            ['Assets compilados', file_exists(public_path('build/manifest.json')), 'Ejecuta npm ci && npm run build.'],
            ['storage escribible', is_writable(storage_path()), 'Da permisos de escritura a storage/.'],
            ['bootstrap/cache escribible', is_writable(base_path('bootstrap/cache')), 'Da permisos de escritura a bootstrap/cache/.'],
        ];

        $failed = 0;

        foreach ($checks as [$label, $passed, $hint]) {
            if ($passed) {
                $this->line("<fg=green>OK</>   {$label}");

                continue;
            }

            $failed++;
            $this->line("<fg=red>FALLA</> {$label}");
            $this->line("       {$hint}");
        }

        if ($failed > 0) {
            $this->newLine();
            $this->error("Hay {$failed} punto(s) pendiente(s) para producción.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Todo listo para producción.');

        return self::SUCCESS;
    }

    private function isLocalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    private function paymentMethodsConfigured(): bool
    {
        $enabled = collect(config('payments.methods'))
            ->filter(fn (array $method): bool => (bool) ($method['enabled'] ?? false));

        if ($enabled->isEmpty()) {
            return false;
        }

        return $enabled->every(fn (array $method): bool => filled($method['phone'] ?? null)
            || filled($method['qr'] ?? null));
    }

    private function internalRecipientsConfigured(): bool
    {
        if (! Schema::hasTable('email_recipients')) {
            return false;
        }

        if ($this->fallbackEmailIsValid()) {
            return true;
        }

        foreach (array_keys(EmailRecipient::TYPE_OPTIONS) as $type) {
            if (! EmailRecipient::query()->active()->ofType($type)->exists()) {
                return false;
            }
        }

        return true;
    }

    private function internalRecipientsHint(): string
    {
        if (! Schema::hasTable('email_recipients')) {
            return 'Ejecuta las migraciones para crear la tabla de destinatarios.';
        }

        if ($this->fallbackEmailIsValid()) {
            return '';
        }

        $missing = [];

        foreach (EmailRecipient::TYPE_OPTIONS as $type => $label) {
            if (! EmailRecipient::query()->active()->ofType($type)->exists()) {
                $missing[] = $label;
            }
        }

        if ($missing === []) {
            return '';
        }

        return 'Faltan destinatarios activos para: '.implode(', ', $missing)
            .'. Define INTERNAL_NOTIFICATION_FALLBACK_EMAIL como respaldo.';
    }

    private function fallbackEmailIsValid(): bool
    {
        $fallback = trim((string) config('notifications.fallback_email'));

        return $fallback !== ''
            && filter_var($fallback, FILTER_VALIDATE_EMAIL) !== false
            && ! str_ends_with($fallback, '@example.com');
    }
}
