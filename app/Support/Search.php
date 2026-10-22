<?php

namespace App\Support;

class Search
{
    /**
     * Carácter de escape para las cláusulas LIKE.
     *
     * No se usa la barra invertida porque su significado depende del driver:
     * MySQL la interpreta como escape por defecto, mientras que PostgreSQL
     * y SQLite no, lo que hacía que el mismo término se buscara de forma
     * distinta en cada base de datos.
     */
    public const ESCAPE = '!';

    /**
     * Construye un patrón LIKE donde el término se busca de forma literal.
     *
     * Escapa los comodines `%` y `_` para que "100%" no coincida con
     * cualquier cosa y "a_b" no coincida con "axb".
     */
    public static function pattern(string $term): string
    {
        $escaped = str_replace(
            [self::ESCAPE, '%', '_'],
            [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'],
            $term
        );

        return '%'.$escaped.'%';
    }
}
