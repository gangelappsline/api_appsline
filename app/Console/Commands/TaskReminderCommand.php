<?php

namespace App\Console\Commands;

use App\Enums\TaskReminderType;
use App\Services\TaskNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Base de los comandos de recordatorio (pendientes / vencen hoy /
 * vencidas). Cada comando concreto solo define su tipo y su consulta;
 * el envío, el --dry-run y el resumen por pantalla viven aquí.
 *
 * Pensados para ejecutarse desde el scheduler (routes/console.php)
 * o a mano desde cron:  php artisan tasks:notify-due-today
 */
abstract class TaskReminderCommand extends Command
{
    /** Tipo de recordatorio del comando concreto. */
    abstract protected function reminderType(): TaskReminderType;

    /** Tareas agrupadas por usuario para este recordatorio. */
    abstract protected function groups(TaskNotificationService $notifications): Collection;

    /** Envío concreto (delega en el servicio de notificaciones). */
    abstract protected function notify(TaskNotificationService $notifications, Collection $groups, bool $dryRun): int;

    /** Mensaje cuando no hay nada que notificar. */
    abstract protected function emptyMessage(): string;

    public function handle(TaskNotificationService $notifications): int
    {
        $groups = $this->groups($notifications);

        if ($userId = $this->option('user')) {
            $groups = $groups
                ->filter(fn (array $group) => $group['user']->id === (int) $userId)
                ->values();
        }

        if ($groups->isEmpty()) {
            $this->info($this->emptyMessage());

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $sent = $this->notify($notifications, $groups, $dryRun);

        $this->table(
            ['Usuario', 'Correo', 'Tareas'],
            $groups->map(fn (array $group) => [
                $group['user']->name,
                $group['user']->email,
                $group['tasks']->count(),
            ])->all()
        );

        $this->info($dryRun
            ? "Modo --dry-run: se enviarían {$sent} correo(s). No se envió ninguno."
            : "«{$this->reminderType()->label()}»: se encolaron {$sent} correo(s).");

        return self::SUCCESS;
    }
}
