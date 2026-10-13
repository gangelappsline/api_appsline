<?php

namespace App\Console\Commands;

use App\Enums\TaskReminderType;
use App\Services\TaskNotificationService;
use Illuminate\Support\Collection;

/**
 * Avisa a cada usuario de las tareas que no ha entregado y cuya fecha
 * ya pasó. Programado de lunes a viernes a las 09:00 (routes/console.php).
 */
class NotifyOverdueTasks extends TaskReminderCommand
{
    protected $signature = 'tasks:notify-overdue
        {--user= : Solo notificar al usuario con este ID}
        {--dry-run : Muestra quién recibiría el correo sin enviarlo}';

    protected $description = 'Envía a cada usuario un correo con sus tareas vencidas sin entregar';

    protected function reminderType(): TaskReminderType
    {
        return TaskReminderType::Overdue;
    }

    protected function groups(TaskNotificationService $notifications): Collection
    {
        return $notifications->overdueTasksByUser();
    }

    protected function notify(TaskNotificationService $notifications, Collection $groups, bool $dryRun): int
    {
        return $notifications->notifyOverdueTasks($groups, $dryRun);
    }

    protected function emptyMessage(): string
    {
        return 'No hay tareas vencidas sin entregar. No se enviaron correos.';
    }
}
