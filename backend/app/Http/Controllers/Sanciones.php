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
        $socio = SocioTitular::find($id_socio);
        if (!$socio) {
            return response()->json(['message' => 'Socio no encontrado'], 404);
        }

        $socio->refresh();
        $retrasos = $socio->retrasos_ludoteca;

        if ($socio->estatus_cuenta === 'CANCELADO') {
            return;
        }

        if ($retrasos == 3) {
            $socio->notify(new AlertaRecogidaNotification(null, 'advertencia'));
            return;
        }

        $diasMap = [5 => 1, 7 => 3, 9 => 5];

        if (isset($diasMap[$retrasos])) {
            $fechaLudoteca = now()->addDays($diasMap[$retrasos]);
            $reservaActiva = $socio->fecha_fin_penalizacion_reserva && $socio->fecha_fin_penalizacion_reserva->isFuture();
            $socio->update([
                'estatus_penalizacion'            => $reservaActiva ? 'PENALIZADO_AMBOS' : 'PENALIZADO_LUDOTECA',
                'fecha_fin_penalizacion_ludoteca' => $fechaLudoteca,
            ]);
            $socio->notify(new AlertaRecogidaNotification(null, 'suspension_ludoteca'));
        } elseif ($retrasos >= 12) {
            $socio->notify(new AlertaRecogidaNotification(null, 'cancelacion_ludoteca'));
            $socio->update([
                'estatus_penalizacion'            => 'SUSPENDIDO',
                'fecha_fin_penalizacion_ludoteca' => null,
                'fecha_fin_penalizacion_reserva'  => null,
            ]);
        }

        return response()->json([
            'message'             => 'Sanciones aplicadas correctamente',
            'retrasos'            => $socio->refresh()->retrasos_ludoteca,
            'estatus_actual'      => $socio->estatus_cuenta,
            'estatus_penalizacion' => $socio->estatus_penalizacion,
        ]);
    }

    public static function aplicarSancionesReservas($id_socio)
    {
        $socio = SocioTitular::find($id_socio);
        if (!$socio) {
            return;
        }

        $socio->refresh();
        $noshows = $socio->contador_no_shows;

        if ($socio->estatus_cuenta === 'CANCELADO') {
            return;
        }

        if ($noshows == 3) {
            $socio->notify(new AlertaRecogidaNotification(null, 'advertencia'));
            return;
        }

        $diasMap = [5 => 1, 7 => 3, 9 => 5];

        if (isset($diasMap[$noshows])) {
            $fechaReserva = now()->addDays($diasMap[$noshows]);
            $ludotecaActiva = $socio->fecha_fin_penalizacion_ludoteca && $socio->fecha_fin_penalizacion_ludoteca->isFuture();
            $socio->update([
                'estatus_penalizacion'           => $ludotecaActiva ? 'PENALIZADO_AMBOS' : 'PENALIZADO_RESERVA',
                'fecha_fin_penalizacion_reserva' => $fechaReserva,
            ]);
            $socio->notify(new AlertaRecogidaNotification(null, 'suspension_ludoteca'));
        } elseif ($noshows >= 12) {
            $socio->update([
                'estatus_penalizacion'            => 'SUSPENDIDO',
                'fecha_fin_penalizacion_ludoteca' => null,
                'fecha_fin_penalizacion_reserva'  => null,
            ]);
            $socio->notify(new AlertaRecogidaNotification(null, 'suspension_ludoteca'));
        }
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