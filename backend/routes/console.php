<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Le decimos que corra tu comando de limpieza cada minuto
Schedule::command('app:clean-expired-reservations')->everyMinute();

Schedule::command('passes:expire')
    ->dailyAt('23:59')
    ->timezone('America/Mexico_City');

Schedule::command('app:calcular-no-shows')
    ->dailyAt('23:50')
    ->timezone('America/Mexico_City');


Schedule::command('ludoteca:wipe-daily')
    ->dailyAt('04:00')
    ->timezone('America/Mexico_City');

Schedule::command('ludoteca:check-alerts')
    ->everyMinute()
    ->timezone('America/Mexico_City');