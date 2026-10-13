<?php

namespace App\Mail;

use App\Models\ProjectTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo que recibe la persona a la que se le asigna una tarea,
 * al crearla o al reasignarla, con toda la descripción de la misma.
 *
 * La vista usa el layout único: resources/views/emails/layouts/master.blade.php
 */
class TaskAssigned extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  ProjectTask  $task  Tarea asignada (se recarga desde la BD al procesar la cola).
     * @param  string|null  $assignerName  Nombre de quien asigna (por defecto, el creador).
     * @param  bool  $reassigned  True cuando la tarea cambió de responsable.
     */
    public function __construct(
        public ProjectTask $task,
        public ?string $assignerName = null,
        public bool $reassigned = false,
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
        $this->task->loadMissing(['project', 'assignedTo', 'user']);

        return new Content(
            view: 'emails.task_assigned',
            with: [
                'task' => $this->task,
                'assigneeName' => $this->task->assignedTo?->name,
                'assignerName' => $this->assignerName ?? $this->task->user?->name,
                'taskUrl' => $this->task->url(),
                // Variables que consume el layout master (título y preheader).
                'subject' => $this->subjectLine(),
                'preheader' => $this->reassigned
                    ? "Se te reasignó la tarea «{$this->task->title}». Revisa su descripción y fecha de entrega."
                    : "Se te asignó la tarea «{$this->task->title}». Revisa su descripción y fecha de entrega.",
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
        return ($this->reassigned ? 'Tarea reasignada: ' : 'Nueva tarea asignada: ').$this->task->title;
    }
}
