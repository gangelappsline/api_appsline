<?php

namespace App\Console\Commands;

use App\Enums\TaskReminderType;
use App\Services\TaskNotificationService;
use Illuminate\Support\Collection;

/**
 * Avisa a cada usuario de todas sus tareas abiertas (pendientes y
 * en progreso). Programado los lunes a las 08:00 en routes/console.php.
 */
class NotifyPendingTasks extends TaskReminderCommand
{
    protected $signature = 'tasks:notify-pending
        {--user= : Solo notificar al usuario con este ID}
        {--dry-run : Muestra quién recibiría el correo sin enviarlo}';

    protected $description = 'Envía a cada usuario un correo con todas sus tareas pendientes';

    protected function reminderType(): TaskReminderType
    {
        return TaskReminderType::Pending;
    }

    protected function groups(TaskNotificationService $notifications): Collection
    {
        return $notifications->pendingTasksByUser();
    }

    protected function notify(TaskNotificationService $notifications, Collection $groups, bool $dryRun): int
    {
        return $notifications->notifyPendingTasks($groups, $dryRun);
    }

    protected function emptyMessage(): string
    {
        return 'No hay tareas pendientes asignadas. No se enviaron correos.';
    }
}
