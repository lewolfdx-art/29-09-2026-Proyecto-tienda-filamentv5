<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Índice ciego: permite buscar un dato que se guarda cifrado.
 *
 * Lo que se almacena es un HMAC del valor, no su hash plano. Un SHA-256 de
 * un teléfono se puede romper por fuerza bruta (el espacio de números de
 * teléfono es pequeño), mientras que con HMAC hace falta la clave de la
 * aplicación para calcularlo. Aun así, el índice revela que dos registros
 * tienen el mismo valor, que es el precio de poder buscarlo.
 */
class BlindIndex
{
    /**
     * Prefijo de país con el que se guardan los teléfonos.
     */
    private const COUNTRY_CODE = '51';

    /**
     * HMAC del valor, con la clave de la aplicación.
     */
    public static function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    /**
     * Normaliza un teléfono a sus dígitos, sin el prefijo de país.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        // Móviles de Perú: +51 seguido de 9 y nueve dígitos.
        if (strlen($digits) === 11 && str_starts_with($digits, self::COUNTRY_CODE) && $digits[2] === '9') {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Índice de un teléfono, o null si no hay un teléfono usable.
     */
    public static function hashPhone(string $phone): ?string
    {
        $digits = self::normalizePhone($phone);

        if ($digits === '') {
            return null;
        }

        return self::hash('phone:'.$digits);
    }

    /**
     * Palabras de un texto, normalizadas (sin acentos y en minúsculas).
     */
    public static function tokens(string $text): array
    {
        $ascii = Str::ascii(Str::lower($text));

        $parts = preg_split('/[^a-z0-9]+/', $ascii, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = array_filter($parts, fn (string $part): bool => strlen($part) >= 3);

        return array_values(array_unique($tokens));
    }

    /**
     * Índice de las palabras de un texto, como lista de hashes separados
     * por comas. Cada palabra se busca con un LIKE, y los hashes son
     * hexadecimal, así que no contienen comodines.
     */
    public static function tokenList(string $text): ?string
    {
        $tokens = self::tokens($text);

        if ($tokens === []) {
            return null;
        }

        return implode(',', array_map(fn (string $token) => self::hash('address:'.$token), $tokens));
    }
}
