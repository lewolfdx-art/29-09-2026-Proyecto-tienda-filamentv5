<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Correo de respaldo para avisos internos
    |--------------------------------------------------------------------------
    |
    | Si un tipo de aviso no tiene destinatarios activos, se usa este correo.
    | Si está vacío y tampoco hay destinatarios, el aviso solo se registra en
    | el log y no se envía.
    |
    */

    'fallback_email' => env(
        'INTERNAL_NOTIFICATION_FALLBACK_EMAIL',
        env('BACKUP_NOTIFICATION_EMAIL'),
    ),

    /*
    |--------------------------------------------------------------------------
    | Backup de base de datos por correo
    |--------------------------------------------------------------------------
    */

    'backup_attachment_max_kb' => (int) env('BACKUP_EMAIL_MAX_ATTACHMENT_KB', 15360),

    'backup_link_ttl_days' => (int) env('BACKUP_EMAIL_LINK_TTL_DAYS', 7),

    'backup_fallback_frequency_days' => (int) env('BACKUP_EMAIL_FALLBACK_FREQUENCY_DAYS', 14),

];
