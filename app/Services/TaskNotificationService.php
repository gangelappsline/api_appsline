<?php

namespace App\Services;

use App\Enums\TaskReminderType;
use App\Mail\TaskAssigned;
use App\Mail\TaskReminder;
use App\Mail\WeeklyTaskReport;
use App\Mail\WeeklyTaskSummary;
use App\Models\ProjectTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Punto único para enviar los correos de tareas.
 *
 * Estas funciones son las que usan:
 *  - los controladores (al crear / reasignar una tarea), y
 *  - los comandos de consola programados en cron
 *    (tasks:notify-pending, tasks:notify-due-today, tasks:notify-overdue
 *    y tasks:weekly-report, ver routes/console.php).
 *
 * Todos los envíos van por la cola (Mail::queue), así que el servidor
 * necesita un worker activo: `php artisan queue:work`.
 */
class TaskNotificationService
{
    // ─────────────────────────────────────────────────────────────────
    // 1) Asignación inmediata (al crear o reasignar una tarea)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Envía el correo de "nueva tarea asignada" al responsable.
     *
     * No envía nada si la tarea no tiene responsable, si el responsable
     * no tiene correo, o si quien asigna es el propio responsable
     * (salvo que tasks.notifications.notify_self_assignment sea true).
     *
     * @return bool True si el correo quedó encolado.
     */
    public function notifyTaskAssigned(ProjectTask $task, ?User $assignedBy = null, bool $reassigned = false): bool
    {
        $task->loadMissing(['project', 'assignedTo', 'user']);

        $assignee = $task->assignedTo;

        if (! $assignee || blank($assignee->email)) {
            return false;
        }

        $assignerId = $assignedBy?->id ?? $task->created_by;
        $notifySelf = (bool) config('tasks.notifications.notify_self_assignment', false);

        if (! $notifySelf && $assignerId !== null && (int) $assignerId === (int) $assignee->id) {
            return false;
        }

        Mail::to($assignee->email)->queue(
            new TaskAssigned($task, $assignedBy?->name ?? $task->user?->name, $reassigned)
        );

        return true;
    }

    // ─────────────────────────────────────────────────────────────────
    // 2) Recordatorios agrupados por usuario (cron)
    // ─────────────────────────────────────────────────────────────────

    /** Tareas abiertas (pendientes + en progreso) agrupadas por responsable. */
    public function pendingTasksByUser(): Collection
    {
        return $this->groupTasksByAssignee(
            ProjectTask::query()->open()
        );
    }

    /** Tareas que tienen que entregarse el día actual, por responsable. */
    public function dueTodayTasksByUser(): Collection
    {
        return $this->groupTasksByAssignee(
            ProjectTask::query()->dueToday()
        );
    }

    /** Tareas no entregadas cuya fecha ya pasó, por responsable. */
    public function overdueTasksByUser(): Collection
    {
        return $this->groupTasksByAssignee(
            ProjectTask::query()->overdue()
        );
    }

    /** Envía el recordatorio de tareas pendientes. Devuelve cuántos correos salieron. */
    public function notifyPendingTasks(?Collection $groups = null, bool $dryRun = false): int
    {
        return $this->sendReminders(
            TaskReminderType::Pending,
            $groups ?? $this->pendingTasksByUser(),
            $dryRun
        );
    }

    /** Envía el recordatorio de tareas que vencen hoy. */
    public function notifyDueTodayTasks(?Collection $groups = null, bool $dryRun = false): int
    {
        return $this->sendReminders(
            TaskReminderType::DueToday,
            $groups ?? $this->dueTodayTasksByUser(),
            $dryRun
        );
    }

    /** Envía el recordatorio de tareas vencidas sin entregar. */
    public function notifyOverdueTasks(?Collection $groups = null, bool $dryRun = false): int
    {
        return $this->sendReminders(
            TaskReminderType::Overdue,
            $groups ?? $this->overdueTasksByUser(),
            $dryRun
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // 3) Reporte semanal (viernes por la noche)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Datos del reporte semanal: por cada usuario con actividad, sus
     * tareas completadas en la semana (lunes → ahora) y las vencidas
     * que siguen abiertas, más los totales del equipo.
     *
     * @return array{
     *     week_start: Carbon,
     *     week_end: Carbon,
     *     users: Collection<int, array{user: User, completed: Collection, overdue: Collection, on_time: int, late: int, open: int}>,
     *     totals: array{completed: int, on_time: int, late: int, overdue: int, users: int}
     * }
     */
    public function weeklyReportData(?Carbon $date = null): array
    {
        $weekEnd = ($date ?? now())->copy();
        $weekStart = $weekEnd->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $completed = ProjectTask::query()
            ->completedBetween($weekStart, $weekEnd)
            ->assigned()
            ->with(['project', 'assignedTo'])
            ->orderBy('completed_at')
            ->get();

        $overdue = ProjectTask::query()
            ->overdue()
            ->assigned()
            ->with(['project', 'assignedTo'])
            ->orderBy('due_date')
            ->get();

        $openCounts = ProjectTask::query()
            ->open()
            ->assigned()
            ->selectRaw('assigned_to, count(*) as total')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        $users = User::query()
            ->whereIn('id', $completed->pluck('assigned_to')->merge($overdue->pluck('assigned_to'))->unique())
            ->get()
            ->keyBy('id');

        $summaries = $users->map(function (User $user) use ($completed, $overdue, $openCounts) {
            $userCompleted = $completed->where('assigned_to', $user->id)->values();
            $userOverdue = $overdue->where('assigned_to', $user->id)->values();

            return [
                'user' => $user,
                'completed' => $userCompleted,
                'overdue' => $userOverdue,
                'on_time' => $userCompleted->filter(fn (ProjectTask $task) => ! $task->wasCompletedLate())->count(),
                'late' => $userCompleted->filter(fn (ProjectTask $task) => $task->wasCompletedLate())->count(),
                'open' => (int) ($openCounts[$user->id] ?? 0),
            ];
        })
            ->filter(fn (array $summary) => filled($summary['user']->email))
            ->sortBy(fn (array $summary) => $summary['user']->name)
            ->values();

        return [
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'users' => $summaries,
            'totals' => [
                'completed' => (int) $summaries->sum(fn (array $summary) => $summary['completed']->count()),
                'on_time' => (int) $summaries->sum('on_time'),
                'late' => (int) $summaries->sum('late'),
                'overdue' => (int) $summaries->sum(fn (array $summary) => $summary['overdue']->count()),
                'users' => $summaries->count(),
            ],
        ];
    }

    /**
     * Envía el reporte semanal: un resumen personal a cada usuario con
     * actividad y el reporte general a los correos configurados en
     * tasks.notifications.weekly_report_recipients.
     *
     * @return array{user_reports: int, summary_recipients: int, week_start: Carbon, week_end: Carbon}
     */
    public function sendWeeklyReports(bool $dryRun = false, bool $sendToUsers = true, bool $sendSummary = true): array
    {
        $data = $this->weeklyReportData();

        $userReports = 0;

        if ($sendToUsers && (bool) config('tasks.notifications.weekly_report_to_users', true)) {
            foreach ($data['users'] as $summary) {
                if (! $dryRun) {
                    try {
                        Mail::to($summary['user']->email)->queue(new WeeklyTaskReport(
                            $summary['user'],
                            $summary['completed'],
                            $summary['overdue'],
                            $data['week_start'],
                            $data['week_end'],
                            [
                                'on_time' => $summary['on_time'],
                                'late' => $summary['late'],
                                'open' => $summary['open'],
                            ],
                        ));
                    } catch (Throwable $e) {
                        Log::error('No se pudo enviar el resumen semanal personal.', [
                            'user_id' => $summary['user']->id,
                            'error' => $e->getMessage(),
                        ]);

                        continue;
                    }
                }

                $userReports++;
            }
        }

        $summaryRecipients = 0;

        if ($sendSummary) {
            $recipients = (array) config('tasks.notifications.weekly_report_recipients', []);

            if ($recipients === []) {
                Log::warning('Reporte semanal: no hay destinatarios configurados en TASK_WEEKLY_REPORT_RECIPIENTS; no se envió el reporte general.');
            }

            foreach ($recipients as $email) {
                if (! $dryRun) {
                    try {
                        Mail::to($email)->queue(new WeeklyTaskSummary(
                            $data['users'],
                            $data['week_start'],
                            $data['week_end'],
                            $data['totals'],
                        ));
                    } catch (Throwable $e) {
                        Log::error('No se pudo enviar el reporte semanal general.', [
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);

                        continue;
                    }
                }

                $summaryRecipients++;
            }
        }

        return [
            'user_reports' => $userReports,
            'summary_recipients' => $summaryRecipients,
            'week_start' => $data['week_start'],
            'week_end' => $data['week_end'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Internos
    // ─────────────────────────────────────────────────────────────────

    /**
     * Agrupa las tareas de una consulta por responsable.
     *
     * @return Collection<int, array{user: User, tasks: Collection<int, ProjectTask>}>
     */
    private function groupTasksByAssignee(Builder $query): Collection
    {
        $tasks = $query
            ->assigned()
            ->with(['project', 'assignedTo'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderBy('title')
            ->get();

        return $tasks
            ->groupBy('assigned_to')
            ->map(fn (Collection $userTasks) => [
                'user' => $userTasks->first()->assignedTo,
                'tasks' => $userTasks->values(),
            ])
            ->filter(fn (array $group) => $group['user'] !== null && filled($group['user']->email))
            ->sortBy(fn (array $group) => $group['user']->name)
            ->values();
    }

    /**
     * Envía un TaskReminder a cada usuario del grupo.
     * Con $dryRun solo cuenta a cuántos se les enviaría.
     */
    private function sendReminders(TaskReminderType $type, Collection $groups, bool $dryRun = false): int
    {
        $sent = 0;

        foreach ($groups as $group) {
            if ($dryRun) {
                $sent++;

                continue;
            }

            try {
                Mail::to($group['user']->email)->queue(
                    new TaskReminder($group['user'], $type, $group['tasks'])
                );

                $sent++;
            } catch (Throwable $e) {
                Log::error('No se pudo enviar el recordatorio de tareas.', [
                    'type' => $type->value,
                    'user_id' => $group['user']->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
