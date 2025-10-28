<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ProjectTask;

class TaskAssigned extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ProjectTask $task;
    public array $meta;

    /**
     * Create a new message instance.
     */
    public function __construct(ProjectTask $task, array $meta = [])
    {
        $this->task = $task;
        $this->meta = $meta;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->meta['subject'] ?? ("Tarea asignada: {$this->task->title}");

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $project = $this->task->project ?? null;
        $projectLogo = null;
        if ($project && !empty($project->logo)) {
            $projectLogo = asset('storage/' . ltrim($project->logo, '/'));
        } elseif (!empty($this->meta['projectLogo'])) {
            $projectLogo = $this->meta['projectLogo'];
        }

        $primaryColor = $this->meta['primaryColor'] ?? config('app.primary_color', '#0ea5a4');

        return new Content(
            view: 'emails.task_assigned',
            with: [
                'task' => $this->task,
                'projectName' => $project->name ?? ($this->meta['projectName'] ?? config('app.name')),
                'projectLogo' => $projectLogo,
                'assigneeName' => $this->meta['assigneeName'] ?? ($this->task->assignee?->name ?? null),
                'assignerName' => $this->meta['assignerName'] ?? null,
                'taskUrl' => $this->meta['taskUrl'] ?? url("/projects/{$this->task->project_id}/tasks/{$this->task->id}"),
                'companyName' => $this->meta['companyName'] ?? config('app.name'),
                'companyContact' => $this->meta['companyContact'] ?? null,
                'primaryColor' => $primaryColor,
            ]
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
