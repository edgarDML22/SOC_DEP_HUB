<?php
namespace App\Http\Controllers;

use App\Models\SocioTitular;
use App\Models\RegistrosLudoteca;
use App\Notifications\AlertaRecogidaNotification;
use Illuminate\Http\Request;

class SancionesController extends Controller
{
    public static function aplicarSanciones(Request $request)
    {
        $id_socio = $request->id_socio;

        // 1. Buscar el socio
        $socio = SocioTitular::find($id_socio);
        if (!$socio) {
            return response()->json(['error' => 'Socio no encontrado'], 404);
        }

        // 2. Aumentar retrasos
        $socio->increment('retrasos_ludoteca');

        // 3. Recargar datos actualizados
        $socio->refresh();
        $retrasos = $socio->retrasos_ludoteca;

        // 4. Lógica de notificaciones/suspensión (NO APLICAR A CANCELADOS)
        if ($socio->estatus_acceso !== 'CANCELADO') {

            if ($retrasos == 3) {
                $socio->notify(new AlertaRecogidaNotification(null, 'advertencia'));

            } elseif ($retrasos == 5) {
                $socio->update([
                    'estatus_acceso' => 'SUSPENSION_TEMPORAL',
                    'fecha_fin_suspension' => now()->addDay()
                ]);

            } elseif ($retrasos == 7) {
                $socio->update([
                    'estatus_acceso' => 'SUSPENSION_TEMPORAL',
                    'fecha_fin_suspension' => now()->addDays(3)
                ]);

            } elseif ($retrasos == 9) {
                $socio->update([
                    'estatus_acceso' => 'SUSPENSION_TEMPORAL',
                    'fecha_fin_suspension' => now()->addDays(5)
                ]);

            } elseif ($retrasos >= 12) {
                $socio->update([
                    'estatus_acceso' => 'CANCELADO',
                    'fecha_fin_suspension' => null
                ]);
            }
        }

        return response()->json([
            'message' => 'Sanciones aplicadas correctamente',
            'retrasos' => $socio->refresh()->retrasos_ludoteca,
            'estatus_actual' => $socio->estatus_acceso
        ]);
    }
}