<?php
namespace App\Http\Controllers;

use App\Models\SocioTitular;
use App\Models\RegistrosLudoteca;
use App\Notifications\AlertaRecogidaNotification;
use Illuminate\Http\Request;

class Sanciones extends Controller
{
    public static function aplicarSanciones($id_socio)
    {
        // 1. Buscar el socio
        $socio = SocioTitular::find($id_socio);
        if (!$socio) {
            return response()->json([
                'message' => 'Socio no encontrado',
            ], 404);
        }

        // 2. Ya fue incrementado en el controlador que llama

        // 3. Recargar datos actualizados
        $socio->refresh();
        $retrasos = $socio->retrasos_ludoteca;

        // 4. Lógica de notificaciones/suspensión (NO APLICAR A CANCELADOS)
        if ($socio->estatus_acceso !== 'CANCELADO') {

            if ($retrasos == 3) {
                $socio->notify(new AlertaRecogidaNotification(null, 'advertencia'));
            } elseif ($retrasos == 5) {
                $socio->update([
                    'estatus_cuenta' => 'PENALIZADO_LUDOTECA',
                    'fecha_fin_penalizacion' => now()->addDay()
                ]);
                $socio->notify(new AlertaRecogidaNotification(null, 'suspension_ludoteca'));

            } elseif ($retrasos == 7) {
                $socio->update([
                    'estatus_cuenta' => 'PENALIZADO_LUDOTECA',
                    'fecha_fin_penalizacion' => now()->addDays(3)

                ]);
                $socio->notify(new AlertaRecogidaNotification(null, 'suspension_ludoteca'));

            } elseif ($retrasos == 9) {
                $socio->update([
                    'estatus_cuenta' => 'PENALIZADO_LUDOTECA',
                    'fecha_fin_penalizacion' => now()->addDays(5)
                ]);
                $socio->notify(new AlertaRecogidaNotification(null, 'suspension_ludoteca'));

            } elseif ($retrasos >= 12) {
                $socio->notify(new AlertaRecogidaNotification(null, 'cancelacion_ludoteca'));
                $socio->update([
                    'estatus_cuenta' => 'SUSPENDIDO',
                    'fecha_fin_penalizacion' => null
                ]);
            }
        }

        return response()->json([
            'message' => 'Sanciones aplicadas correctamente',
            'retrasos' => $socio->refresh()->retrasos_ludoteca,
            'estatus_actual' => $socio->estatus_acceso
        ]);
    }
    public static function aplicarSancionesReservas($id_socio)
    {
        // 1. Buscar el socio
        $socio = SocioTitular::find($id_socio);
        if (!$socio) {
            return;
        }

        // 2. Ya fue incrementado en el controlador que llama

        // 3. Recargar datos actualizados
        $socio->refresh();
        $noshows = $socio->contador_noshows;

        // 4. Lógica de notificaciones/suspensión (NO APLICAR A CANCELADOS)
        if ($socio->estatus_acceso !== 'CANCELADO') {

            if ($noshows == 3) {
                $socio->notify(new AlertaRecogidaNotification(null, 'advertencia'));

            } elseif ($noshows == 5) {
                $socio->update([
                    'estatus_cuenta' => 'PENALIZADO_RESERVA',
                    'fecha_fin_suspension' => now()->addDay()
                ]);

            } elseif ($noshows == 7) {
                $socio->update([
                    'estatus_cuenta' => 'PENALIZADO_RESERVA',
                    'fecha_fin_suspension' => now()->addDays(3)
                ]);

            } elseif ($noshows == 9) {
                $socio->update([
                    'estatus_cuenta' => 'PENALIZADO_RESERVA',
                    'fecha_fin_suspension' => now()->addDays(5)
                ]);

            } elseif ($noshows >= 12) {
                $socio->update([
                    'estatus_cuenta' => 'SUSPENDIDO',
                    'fecha_fin_suspension' => null
                ]);
                $socio->notify(new AlertaRecogidaNotification(null, 'suspension_ludoteca'));
            }
        }

        return response()->json([
            'message' => 'Sanciones aplicadas correctamente',
            'retrasos' => $socio->refresh()->retrasos_ludoteca,
            'estatus_actual' => $socio->estatus_acceso
        ]);
    }

    /**
     * Endpoints para la API
     */
    public function aplicarSancionesAPI(Request $request)
    {
        return self::aplicarSanciones($request->id_socio);
    }

    public function aplicarSancionesReservasAPI(Request $request)
    {
        return self::aplicarSancionesReservas($request->id_socio);
    }
}