<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Expresiones SQL de fecha portables entre los motores soportados.
 *
 * Evita depender de funciones propias de MySQL (MONTH, DAYOFWEEK, HOUR)
 * cuando la aplicación puede correr sobre SQLite u otro motor.
 */
class DateExpressions
{
    public static function month(string $column): string
    {
        return match (self::driver()) {
            'sqlite' => "CAST(strftime('%m', {$column}) AS INTEGER)",
            'pgsql' => "EXTRACT(MONTH FROM {$column})::int",
            'sqlsrv' => "MONTH({$column})",
            default => "MONTH({$column})",
        };
    }

    public static function hour(string $column): string
    {
        return match (self::driver()) {
            'sqlite' => "CAST(strftime('%H', {$column}) AS INTEGER)",
            'pgsql' => "EXTRACT(HOUR FROM {$column})::int",
            'sqlsrv' => "DATEPART(hour, {$column})",
            default => "HOUR({$column})",
        };
    }

    /**
     * Día de la semana con 1 = domingo y 7 = sábado en todos los motores.
     */
    public static function dayOfWeek(string $column): string
    {
        return match (self::driver()) {
            'sqlite' => "CAST(strftime('%w', {$column}) AS INTEGER) + 1",
            'pgsql' => "EXTRACT(DOW FROM {$column})::int + 1",
            'sqlsrv' => "DATEPART(weekday, {$column})",
            default => "DAYOFWEEK({$column})",
        };
    }

    public static function date(string $column): string
    {
        return match (self::driver()) {
            'sqlite' => "date({$column})",
            'pgsql' => "CAST({$column} AS date)",
            'sqlsrv' => "CAST({$column} AS date)",
            default => "DATE({$column})",
        };
    }

    private static function driver(): string
    {
        return DB::connection()->getDriverName();
    }
}
