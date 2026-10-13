<?php

namespace App\Mail;

use App\Enums\TaskReminderType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Correo agrupado (un solo envío por usuario) con sus tareas:
 * pendientes, que vencen hoy o vencidas, según $type.
 * Lo disparan los comandos programados en cron (ver routes/console.php).
 *
 * La vista usa el layout único: resources/views/emails/layouts/master.blade.php
 */
class TaskReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\ProjectTask>  $tasks  Tareas del usuario para este aviso.
     */
    public function __construct(
        public User $user,
        public TaskReminderType $type,
        public Collection $tasks,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->type->subject($this->tasks->count()),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $count = $this->tasks->count();

        return new Content(
            view: 'emails.tasks.reminder',
            with: [
                'recipient' => $this->user,
                'type' => $this->type,
                'tasks' => $this->tasks,
                'count' => $count,
                'projectsUrl' => rtrim((string) config('tasks.frontend_url'), '/').'/projects',
                // Variables que consume el layout master (título y preheader).
                'subject' => $this->type->subject($count),
                'preheader' => $this->type->preheader($count),
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
}
