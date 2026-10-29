<?php

namespace App\Support;

use App\Mail\InternalNotice;
use App\Models\EmailRecipient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class InternalNotifier
{
    public static function send(
        string $type,
        string $subject,
        string $body,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
    ): void {
        $emails = self::emailsFor($type);

        if ($emails === []) {
            return;
        }

        Mail::to($emails)->queue(new InternalNotice($subject, $body, $actionUrl, $actionLabel));
    }

    public static function backupFailed(string $message): void
    {
        self::send(
            EmailRecipient::TYPE_BACKUP_FAILURE,
            'Falla en el backup de la base de datos',
            $message,
        );
    }

    /**
     * @return array<int, string>
     */
    private static function emailsFor(string $type): array
    {
        $recipients = self::activeRecipients($type)
            ->map(fn (EmailRecipient $recipient): string => $recipient->effectiveEmail())
            ->all();

        $emails = self::validEmails($recipients);

        if ($emails !== []) {
            return $emails;
        }

        $fallback = trim((string) config('notifications.fallback_email'));

        Log::warning('No hay destinatarios activos para el aviso interno.', [
            'tipo' => $type,
            'correo_respaldo' => $fallback !== '' ? $fallback : null,
        ]);

        if ($fallback === '' || filter_var($fallback, FILTER_VALIDATE_EMAIL) === false) {
            Log::warning('El aviso interno no se envió porque tampoco hay un correo de respaldo válido.', [
                'tipo' => $type,
            ]);

            return [];
        }

        return [mb_strtolower($fallback)];
    }

    /**
     * @return Collection<int, EmailRecipient>
     */
    private static function activeRecipients(string $type): Collection
    {
        if (! Schema::hasTable('email_recipients')) {
            return new Collection;
        }

        return EmailRecipient::query()
            ->with('user')
            ->active()
            ->ofType($type)
            ->get();
    }

    /**
     * @param  array<int, string>  $emails
     * @return array<int, string>
     */
    private static function validEmails(array $emails): array
    {
        $valid = [];

        foreach ($emails as $email) {
            $email = mb_strtolower(trim($email));

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }

            $valid[$email] = $email;
        }

        return array_values($valid);
    }
}
