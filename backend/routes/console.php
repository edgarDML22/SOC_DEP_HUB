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

// Revisar y levantar penalizaciones de 7 días que hayan vencido
Schedule::command('app:levantar-penalizaciones')
    ->dailyAt('00:05')
    ->timezone('America/Mexico_City');

// No-shows en reservas de espacios: cada hora al minuto 20 (07:20–23:20)
Schedule::command('app:no-show-on-demand')
    ->hourlyAt(20)
    ->between('07:00', '23:59')
    ->timezone('America/Mexico_City');

// Limpieza semanal de reservas fantasma (uso de mantenimiento)
Schedule::command('app:no-show-on-demand-all')
    ->weeklyOn(0, '04:00')
    ->timezone('America/Mexico_City');


Schedule::command('ludoteca:wipe-daily')
    ->dailyAt('04:00')
    ->timezone('America/Mexico_City');

Schedule::command('ludoteca:check-alerts')
    ->everyMinute()
    ->timezone('America/Mexico_City');

// Limpia tokens de Sanctum expirados (>30 días) — mantiene personal_access_tokens pequeña
Schedule::command('sanctum:prune-expired --hours=720')->daily();