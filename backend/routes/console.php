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

// Procesa colas pendientes (default + cancelación de torneos) cuando el worker dedicado no las alcance
Schedule::command('queue:work --queue=default,torneo-cancelacion --stop-when-empty --tries=3 --timeout=120')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->timezone('America/Mexico_City');

// ── Máquina de estados de sesiones de clases ──────────────────────────────────
// DISPONIBLE | LLENA → EN_CURSO → FINALIZADA
// Condición de negocio: plantilla publicada y vigente para la fecha de la sesión.
// Lecturas de hora_inicio / hora_fin desde el snapshot de sesiones_activas (sin JOIN a plantilla).
Schedule::command('sessions:update-status')
    ->everyMinute()
    ->withoutOverlapping()
    ->timezone('America/Mexico_City');