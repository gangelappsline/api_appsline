<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Reporte semanal GENERAL del equipo (viernes por la noche): todas las
 * tareas completadas en la semana y las vencidas, con el resumen por
 * usuario. Se envía a los correos de config('tasks.notifications').
 *
 * La vista usa el layout único: resources/views/emails/layouts/master.blade.php
 */
class WeeklyTaskSummary extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, array{user: \App\Models\User, completed: Collection, overdue: Collection, on_time: int, late: int, open: int}>  $summaries
     * @param  array{completed: int, on_time: int, late: int, overdue: int, users: int}  $totals
     */
    public function __construct(
        public Collection $summaries,
        public Carbon $weekStart,
        public Carbon $weekEnd,
        public array $totals = [],
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $totals = array_merge([
            'completed' => $this->summaries->sum(fn ($summary) => $summary['completed']->count()),
            'on_time' => $this->summaries->sum('on_time'),
            'late' => $this->summaries->sum('late'),
            'overdue' => $this->summaries->sum(fn ($summary) => $summary['overdue']->count()),
            'users' => $this->summaries->count(),
        ], $this->totals);

        return new Content(
            view: 'emails.tasks.weekly-summary',
            with: [
                'summaries' => $this->summaries,
                'weekStart' => $this->weekStart,
                'weekEnd' => $this->weekEnd,
                'totals' => $totals,
                'projectsUrl' => rtrim((string) config('tasks.frontend_url'), '/').'/projects',
                // Variables que consume el layout master (título y preheader).
                'subject' => $this->subjectLine(),
                'preheader' => "Reporte del equipo: {$totals['completed']} completada(s) y {$totals['overdue']} vencida(s) esta semana.",
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function subjectLine(): string
    {
        return 'Reporte semanal de tareas del equipo · '
            .$this->weekStart->translatedFormat('d M').' – '.$this->weekEnd->translatedFormat('d M Y');
    }
}
