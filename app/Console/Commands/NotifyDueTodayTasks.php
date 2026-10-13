<?php

namespace App\Console\Commands;

use App\Enums\TaskReminderType;
use App\Services\TaskNotificationService;
use Illuminate\Support\Collection;

/**
 * Avisa a cada usuario de las tareas que tiene que entregar el día
 * actual. Programado todos los días a las 08:00 en routes/console.php.
 */
class NotifyDueTodayTasks extends TaskReminderCommand
{
    protected $signature = 'tasks:notify-due-today
        {--user= : Solo notificar al usuario con este ID}
        {--dry-run : Muestra quién recibiría el correo sin enviarlo}';

    protected $description = 'Envía a cada usuario un correo con las tareas que vencen hoy';

    protected function reminderType(): TaskReminderType
    {
        return TaskReminderType::DueToday;
    }

    protected function groups(TaskNotificationService $notifications): Collection
    {
        return $notifications->dueTodayTasksByUser();
    }

    protected function notify(TaskNotificationService $notifications, Collection $groups, bool $dryRun): int
    {
        return $notifications->notifyDueTodayTasks($groups, $dryRun);
    }

    protected function emptyMessage(): string
    {
        return 'Ninguna tarea vence hoy. No se enviaron correos.';
    }
}
