<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Notificaciones de tareas por correo (cron)
|--------------------------------------------------------------------------
|
| En el servidor basta UNA línea de cron que ejecute el scheduler
| cada minuto, y un worker de colas para que los correos salgan:
|
|   * * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
|   php artisan queue:work --tries=3        (con supervisor/systemd)
|
| Los horarios usan la zona horaria de la app (APP_TIMEZONE en el .env,
| p. ej. America/Mexico_City). Documentación: docs/EMAIL_NOTIFICATIONS.md
|
*/

// Resumen de todas las tareas pendientes: lunes al arrancar la semana.
Schedule::command('tasks:notify-pending')
    ->mondays()
    ->at('08:00')
    ->withoutOverlapping();

// Tareas que se entregan el día actual: aviso cada mañana.
Schedule::command('tasks:notify-due-today')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// Tareas no entregadas con la fecha ya pasada: recordatorio entre semana.
Schedule::command('tasks:notify-overdue')
    ->weekdays()
    ->at('09:00')
    ->withoutOverlapping();

// Reporte semanal: viernes por la noche, completadas + vencidas por usuario.
Schedule::command('tasks:weekly-report')
    ->fridays()
    ->at('20:00')
    ->withoutOverlapping();
