<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libera las reservas de stock de los pedidos que el cliente no pagó a tiempo.
Schedule::command('orders:expire')->everyFiveMinutes()->withoutOverlapping();
