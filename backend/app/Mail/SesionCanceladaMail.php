<?php

namespace App\Mail;

use App\Models\SesionActiva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SesionCanceladaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SesionActiva $sesion,
        public readonly ?string $nombreDestinatario = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sesión Cancelada - SOC-DEP HUB');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sesion-cancelada');
    }
}
