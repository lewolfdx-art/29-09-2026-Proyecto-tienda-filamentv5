<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libera las reservas de stock de los pedidos que el cliente no pagó a tiempo.
Schedule::command('orders:expire')->everyFiveMinutes()->withoutOverlapping();

// Copia de seguridad diaria de la base de datos y de storage/app/public.
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('03:00')->withoutOverlapping();

// Poda el registro de auditoría según config/activitylog.php.
Schedule::command('activitylog:clean --force')->monthlyOn(1, '03:30')->withoutOverlapping();
