<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Resumen semanal PERSONAL (viernes por la noche): las tareas que el
 * usuario completó en la semana y las que siguen vencidas.
 *
 * La vista usa el layout único: resources/views/emails/layouts/master.blade.php
 */
class WeeklyTaskReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\ProjectTask>  $completed  Completadas en la semana.
     * @param  Collection<int, \App\Models\ProjectTask>  $overdue    Vencidas al momento del envío.
     * @param  array{on_time?: int, late?: int, open?: int}  $stats  Conteos extra del usuario.
     */
    public function __construct(
        public User $user,
        public Collection $completed,
        public Collection $overdue,
        public Carbon $weekStart,
        public Carbon $weekEnd,
        public array $stats = [],
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
        return new Content(
            view: 'emails.tasks.weekly-report',
            with: [
                'recipient' => $this->user,
                'completed' => $this->completed,
                'overdue' => $this->overdue,
                'weekStart' => $this->weekStart,
                'weekEnd' => $this->weekEnd,
                'stats' => array_merge([
                    'on_time' => $this->completed->filter(fn ($task) => ! $task->wasCompletedLate())->count(),
                    'late' => $this->completed->filter(fn ($task) => $task->wasCompletedLate())->count(),
                    'open' => $this->overdue->count(),
                ], $this->stats),
                'projectsUrl' => rtrim((string) config('tasks.frontend_url'), '/').'/projects',
                // Variables que consume el layout master (título y preheader).
                'subject' => $this->subjectLine(),
                'preheader' => "Tu semana: {$this->completed->count()} tarea(s) completada(s) y {$this->overdue->count()} vencida(s).",
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
        return 'Tu resumen semanal de tareas · '
            .$this->weekStart->translatedFormat('d M').' – '.$this->weekEnd->translatedFormat('d M Y');
    }
}
