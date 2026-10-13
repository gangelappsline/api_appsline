# Notificaciones de tareas por correo

Sistema de correos automáticos de tareas. Todos los correos usan el layout
único `resources/views/emails/layouts/master.blade.php` (header y footer de
appsline) y los componentes de `resources/views/components/email/`.

## Qué correos existen

| Correo | Cuándo se envía | Clase (Mailable) | Vista |
|---|---|---|---|
| Nueva tarea asignada | Al **crear** una tarea con responsable y al **reasignarla** a otra persona desde la API | `App\Mail\TaskAssigned` | `emails/task_assigned.blade.php` |
| Tareas pendientes | Lunes 08:00 — resumen de todas las tareas abiertas del usuario | `App\Mail\TaskReminder` (`pending`) | `emails/tasks/reminder.blade.php` |
| Vencen hoy | Todos los días 08:00 — tareas que se entregan el día actual | `App\Mail\TaskReminder` (`due_today`) | `emails/tasks/reminder.blade.php` |
| Tareas vencidas | Lunes a viernes 09:00 — no entregadas con la fecha ya pasada | `App\Mail\TaskReminder` (`overdue`) | `emails/tasks/reminder.blade.php` |
| Resumen semanal personal | Viernes 20:00 — completadas de la semana + vencidas del usuario | `App\Mail\WeeklyTaskReport` | `emails/tasks/weekly-report.blade.php` |
| Reporte semanal del equipo | Viernes 20:00 — **todas** las completadas y vencidas, con resumen por usuario | `App\Mail\WeeklyTaskSummary` | `emails/tasks/weekly-summary.blade.php` |

Toda la lógica de envío vive en `App\Services\TaskNotificationService`:
los controladores y los comandos de consola solo llaman a ese servicio,
así que las mismas funciones se pueden reutilizar desde cualquier otro
sitio (otro comando, un job, Tinker, etc.).

### Definiciones que usan los avisos

- **Pendiente (abierta):** `status` en `pending` o `in_progress`.
- **Vence hoy:** abierta y `due_date` es el día actual.
- **Vencida:** abierta y `due_date` anterior a hoy (las que vencen hoy se
  avisan como "vencen hoy", no como vencidas, para no duplicar avisos).
- **Semana del reporte:** de lunes 00:00 al momento del envío (viernes
  20:00). Una tarea completada cuenta como *tarde* si `completed_at` es
  posterior a su `due_date`.
- En el reporte semanal solo aparecen usuarios con al menos una tarea
  completada en la semana o una tarea vencida.

### Reglas del correo de asignación

- Se envía al crear la tarea **con** `assigned_to` y cuando `assigned_to`
  cambia a otra persona en una actualización (`PUT /api/projects/{project_id}/tasks/{id}`).
- Si la tarea ya tenía responsable, el asunto es «Tarea reasignada: …».
- Por defecto **no** se avisa cuando alguien se asigna la tarea a sí
  mismo (p. ej. subtareas propias). Se puede activar con
  `TASK_NOTIFY_SELF_ASSIGNMENT=true`.

## Comandos (para cron)

```bash
php artisan tasks:notify-pending     # tareas pendientes (abiertas)
php artisan tasks:notify-due-today   # se entregan el día actual
php artisan tasks:notify-overdue     # no entregadas, fecha pasada
php artisan tasks:weekly-report      # reporte del viernes
```

Opciones útiles:

```bash
php artisan tasks:notify-due-today --dry-run   # muestra quién recibiría el correo, sin enviar
php artisan tasks:notify-overdue --user=5      # solo un usuario (para probar)
php artisan tasks:weekly-report --dry-run
php artisan tasks:weekly-report --no-summary   # solo resúmenes personales
php artisan tasks:weekly-report --no-users     # solo el reporte general
```

Los horarios están programados en `routes/console.php`:

```php
Schedule::command('tasks:notify-pending')->mondays()->at('08:00');
Schedule::command('tasks:notify-due-today')->dailyAt('08:00');
Schedule::command('tasks:notify-overdue')->weekdays()->at('09:00');
Schedule::command('tasks:weekly-report')->fridays()->at('20:00');
```

Para cambiar un horario basta editar esa línea; para ver lo programado:
`php artisan schedule:list`.

## Puesta en marcha en el servidor

1. **Zona horaria.** Los horarios y el cálculo de «hoy» usan la zona de la
   app. Define en el `.env`:

   ```dotenv
   APP_TIMEZONE=America/Mexico_City
   APP_LOCALE=es            # opcional: fechas de los correos en español
   ```

2. **Remitente y transporte.** Configura `MAIL_MAILER=smtp` (o el servicio
   que uses) y `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` en el `.env`.

3. **Enlaces de los correos.** Los botones enlazan a
   `FRONTEND_URL/projects/{project_id}/tasks/{id}`. Define `FRONTEND_URL`
   con el dominio del frontend (si es el mismo que la API, vale `APP_URL`).

4. **Destinatarios del reporte general** (viernes):

   ```dotenv
   TASK_WEEKLY_REPORT_RECIPIENTS=jefatura@appsline.com.mx,pm@appsline.com.mx
   ```

   Sin este dato el reporte general no se envía (queda aviso en el log) y
   el comando lo indica por pantalla. El resumen personal de cada usuario
   se controla con `TASK_WEEKLY_REPORT_TO_USERS=true|false`.

5. **Cron del servidor.** Una sola línea que ejecuta el scheduler de
   Laravel cada minuto:

   ```cron
   * * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
   ```

   Si prefieres no usar el scheduler, puedes llamar a cada comando
   directamente desde cron con su propio horario; el resultado es el mismo.

6. **Worker de colas.** Todos los correos se encolan
   (`QUEUE_CONNECTION=database` por defecto), así que debe estar corriendo:

   ```bash
   php artisan queue:work --tries=3
   ```

   En producción lo normal es mantenerlo con Supervisor o un servicio de
   systemd. Sin worker, los correos se quedan en la tabla `jobs` y no
   salen (los comandos seguirán diciendo que se «encolaron»).

## Probar sin enviar correos de verdad

```bash
# Ver quién recibiría cada aviso y con cuántas tareas:
php artisan tasks:notify-pending --dry-run
php artisan tasks:notify-due-today --dry-run
php artisan tasks:notify-overdue --dry-run
php artisan tasks:weekly-report --dry-run

# Enviar solo a un usuario (con MAIL_MAILER=log quedan en storage/logs/laravel.log):
php artisan tasks:notify-due-today --user=5
```

## Tests

```bash
php artisan test tests/Feature/TaskNotificationsTest.php
```

Cubren: correo al crear y al reasignar (y que no se envíe cuando no toca),
agrupación por usuario de los tres recordatorios, `--dry-run` y el
resumen semanal por usuario.
