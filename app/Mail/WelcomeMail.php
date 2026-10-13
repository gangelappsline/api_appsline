<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenido a '.env('APP_NAME', 'Appsline'),
        );
    }

    protected function viewData(): array
    {
        return [
            'name'          => $this->user->name,
            'email'         => $this->user->email,
            'plan'          => 'Pro · 12 meses',
            'workspace'     => Str::slug($this->user->name),
            'activationUrl' => '',
            'steps'         => [
                '<strong>Crea tu primer proyecto</strong> y conecta tu repositorio de Git.',
                '<strong>Invita a tu equipo</strong>: los roles se gestionan desde Panel &rarr; Equipo.',
                '<strong>Activa los reportes</strong> para recibir el resumen cada lunes.',
            ],
        ];
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: $this->viewData(),
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
