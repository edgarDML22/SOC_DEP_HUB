<?php

namespace App\Mail;

use App\Models\SesionActiva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SesionCanceladaInstructorMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SesionActiva $sesion,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Aviso de Cancelación de Sesión - SOC-DEP HUB');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sesion-cancelada-instructor');
    }
}
