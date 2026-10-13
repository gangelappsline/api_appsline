<?php

namespace App\Console\Commands;

use App\Services\TaskNotificationService;
use Illuminate\Console\Command;

/**
 * Reporte de fin de semana (viernes por la noche, 20:00):
 *  - cada usuario con actividad recibe su resumen personal
 *    (completadas de la semana + vencidas), y
 *  - los correos de TASK_WEEKLY_REPORT_RECIPIENTS reciben el reporte
 *    general con el resumen por usuario de todo el equipo.
 */
class SendWeeklyTaskReport extends Command
{
    protected $signature = 'tasks:weekly-report
        {--dry-run : Muestra el resumen sin enviar correos}
        {--no-users : No enviar el resumen personal a cada usuario}
        {--no-summary : No enviar el reporte general del equipo}';

    protected $description = 'Envía el reporte semanal de tareas completadas y vencidas (resumen por usuario)';

    public function handle(TaskNotificationService $notifications): int
    {
        $data = $notifications->weeklyReportData();

        $this->info('Semana del '.$data['week_start']->translatedFormat('d M Y')
            .' al '.$data['week_end']->translatedFormat('d M Y').':');

        if ($data['users']->isEmpty()) {
            $this->info('Sin tareas completadas ni vencidas esta semana. Solo se enviaría el reporte general (si hay destinatarios).');
        } else {
            $this->table(
                ['Usuario', 'Completadas', 'A tiempo', 'Tarde', 'Vencidas', 'Abiertas'],
                $data['users']->map(fn (array $summary) => [
                    $summary['user']->name,
                    $summary['completed']->count(),
                    $summary['on_time'],
                    $summary['late'],
                    $summary['overdue']->count(),
                    $summary['open'],
                ])->all()
            );
        }

        $result = $notifications->sendWeeklyReports(
            dryRun: (bool) $this->option('dry-run'),
            sendToUsers: ! $this->option('no-users'),
            sendSummary: ! $this->option('no-summary'),
        );

        $prefix = $this->option('dry-run') ? 'Modo --dry-run: se enviarían ' : 'Se encolaron ';

        $this->info($prefix."{$result['user_reports']} resumen(es) personales y el reporte general a {$result['summary_recipients']} destinatario(s).");

        if ($result['summary_recipients'] === 0 && ! $this->option('no-summary')) {
            $this->warn('El reporte general no tiene destinatarios: define TASK_WEEKLY_REPORT_RECIPIENTS en el .env.');
        }

        return self::SUCCESS;
    }
}
