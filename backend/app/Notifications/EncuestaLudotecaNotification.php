<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Http\Request;
use App\Models\RegistrosLudoteca;

class EncuestaLudotecaNotification extends Notification
{
    public function submitSurvey(Request $request)
    {
        $request->validate([
            'id_registro' => 'required',
            'calificacion' => 'required',
            'comentarios' => 'nullable'
        ]);

        RegistrosLudoteca::where(
            'id_registro',
            $request->id_registro
        )->update([
                    'calificacion_servicio' => $request->calificacion,
                    'comentarios_padre' => $request->comentarios
                ]);

        return response()->json([
            'message' => 'Encuesta guardada'
        ]);
    }
}
