<?php

namespace App\Notifications;

use App\Models\Torneo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TorneoCanceladoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Torneo $torneo;

    public string $motivo;

    /**
     * Create a new message instance.
     */
    public function __construct(Torneo $torneo, string $motivo)
    {
        $this->torneo = $torneo;
        $this->motivo = $motivo;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("Torneo cancelado: {$this->torneo->nombre_torneo}")
            ->view('emails.torneo-cancelado', [
                'torneo' => $this->torneo,
                'motivo' => $this->motivo,
            ]);
    }
}