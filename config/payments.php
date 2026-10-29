<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Métodos de pago manuales
    |--------------------------------------------------------------------------
    |
    | Yape y Plin no exponen una API pública para comercios sin un PSP. Este
    | flujo muestra los datos de la cuenta, recibe el comprobante del cliente
    | y deja que el equipo lo valide desde el panel de administración.
    |
    | Configura el teléfono, el titular y (opcionalmente) la ruta pública del
    | QR que comparte la app. Ejemplo de QR: payment-qr/yape.png dentro del
    | disco "public".
    |
    */

    'methods' => [
        'yape' => [
            'label' => 'Yape',
            'enabled' => (bool) env('PAYMENT_YAPE_ENABLED', true),
            'phone' => env('PAYMENT_YAPE_PHONE'),
            'holder' => env('PAYMENT_HOLDER', env('APP_NAME')),
            'qr' => env('PAYMENT_YAPE_QR'),
            'instructions' => 'Abre tu app Yape, paga al número indicado y guarda la captura de pantalla.',
        ],
        'plin' => [
            'label' => 'Plin',
            'enabled' => (bool) env('PAYMENT_PLIN_ENABLED', true),
            'phone' => env('PAYMENT_PLIN_PHONE'),
            'holder' => env('PAYMENT_HOLDER', env('APP_NAME')),
            'qr' => env('PAYMENT_PLIN_QR'),
            'instructions' => 'Abre tu app Plin, paga al número indicado y guarda la captura de pantalla.',
        ],
    ],

    // Los comprobantes contienen datos sensibles: se guardan en el disco
    // privado y solo se sirven mediante URLs temporales firmadas.
    'proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),

    'proof_directory' => 'payment-proofs',

    // Minutos que vive el enlace firmado para ver un comprobante.
    'proof_url_ttl' => (int) env('PAYMENT_PROOF_URL_TTL', 15),

    'max_proof_kb' => 4096,

];
