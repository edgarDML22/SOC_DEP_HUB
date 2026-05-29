<?php

namespace App\Http\Controllers;

use App\Models\CodigoQr;
use App\Models\EncuentrosTorneo;
use App\Models\MiembrosFamiliares;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterEventController extends Controller
{
    /**
     * POST /api/v1/instructor/register-event
     *
     * Registra la asistencia de uno o varios socios a una actividad.
     *
     * Body:
     *   tipo_actividad  string   'reserva' | 'sesion' | 'torneo'
     *   id_actividad    int      PK de la actividad según tipo:
     *                              reserva → reservaciones_on_demand.id_reserva
     *                              sesion  → sesiones_activas.id_sesion
     *                              torneo  → encuentros_torneo.id_encuentro
     *   codigos_qr      string[] Lista de códigos QR a registrar (1 para reserva, 1-N para sesion, 1-4 para torneo)
     *   metodo          string   'ESCANER_QR' | 'INGRESO_MANUAL'
     */
    public function register_event(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_actividad' => 'required|in:reserva,sesion,torneo',
            'id_actividad'   => 'required|integer',
            'codigos_qr'     => 'required|array|min:1',
            'codigos_qr.*'   => 'required|string',
            'metodo'         => 'required|in:ESCANER_QR,INGRESO_MANUAL',
        ]);

        $tipo        = $request->input('tipo_actividad');
        $idActividad = (int) $request->input('id_actividad');

        // ── 1. Resolver la actividad según el tipo ────────────────────────────
        $actividad = match ($tipo) {
            'reserva' => Reservacion::find($idActividad),
            'sesion'  => SesionActiva::withoutGlobalScopes()->lockForUpdate()->find($idActividad),
            'torneo'  => EncuentrosTorneo::find($idActividad),
        };

        if (!$actividad) {
            $label = match ($tipo) {
                'reserva' => 'Reservación',
                'sesion'  => 'Sesión',
                'torneo'  => 'Encuentro de torneo',
            };
            return response()->json([
                'success' => false,
                'message' => "{$label} no encontrada.",
            ], 404);
        }

        // ── 2. Resolver y validar cada código QR ─────────────────────────────
        $codigosNormalizados = array_map('strtoupper', $request->input('codigos_qr'));

        $codigosQr = CodigoQr::whereIn('codigo', $codigosNormalizados)
            ->where('estatus', 'ACTIVO')
            ->get()
            ->keyBy('codigo');

        $invalidos = array_diff($codigosNormalizados, $codigosQr->keys()->toArray());
        if (\count($invalidos) > 0) {
            return response()->json([
                'success'           => false,
                'message'           => 'Uno o más códigos QR no son válidos o están expirados.',
                'codigos_invalidos' => array_values($invalidos),
            ], 404);
        }

        // ── 3. Procesar registros en transacción ──────────────────────────────
        return DB::transaction(function () use ($tipo, $actividad, $codigosQr, $codigosNormalizados): JsonResponse {

            return match ($tipo) {
                'reserva' => $this->procesarReserva($actividad, $codigosQr),
                // TODO: sesion  → pase de lista con deduplicación y control de aforo
                // TODO: torneo  → registrar presencia de hasta 4 participantes en el encuentro
                default   => response()->json(['success' => false, 'message' => 'Tipo de actividad no implementado.'], 501),
            };
        });
    }

    // ── Caso: reserva on-demand ───────────────────────────────────────────────
    //
    // Valida que la reserva esté ACTIVA y que el código QR escaneado pertenezca
    // al mismo socio titular que creó la reserva. Si coincide, la marca COMPLETADA.
    private function procesarReserva(Reservacion $reserva, $codigosQr): JsonResponse
    {
        // ── A. Verificar que la reserva esté activa ───────────────────────────
        if ($reserva->estatus_operativo !== 'ACTIVA') {
            return response()->json([
                'success' => false,
                'message' => "La reservación no está activa (estatus actual: {$reserva->estatus_operativo}).",
            ], 409);
        }

        // ── B. Obtener el socio del código QR (ya validado como ACTIVO) ───────
        // codigos_qr tiene exactamente un elemento para reservas
        $codigoQr = $codigosQr->first();

        if ($codigoQr->tipo_usuario !== 'SOCIO') {
            return response()->json([
                'success' => false,
                'message' => 'Solo el socio titular puede registrar el ingreso de una reservación.',
            ], 403);
        }

        $idUsuarioQr = $codigoQr->usuario_id;       // ID_USER_1

        // ── C. Comparar con el titular de la reserva ──────────────────────────
        $idTitularReserva = $reserva->id_socio_titular; // ID_USER_2

        if ($idUsuarioQr !== $idTitularReserva) {
            return response()->json([
                'success' => false,
                'message' => 'El código QR no corresponde al socio titular de esta reservación.',
            ], 403);
        }

        // ── Marcar la reserva como completada ────────────────────────────────
        $reserva->update(['estatus_operativo' => 'COMPLETADA']);

        $socio  = SocioTitular::select('nombre_completo', 'numero_accion')->find($idTitularReserva);
        $nombre = $socio?->nombre_completo ?? "Socio #{$idTitularReserva}";

        return response()->json([
            'success' => true,
            'message' => 'Ingreso registrado correctamente. Reservación completada.',
            'data'    => [
                'id_reserva'     => $reserva->id_reserva,
                'id_usuario'     => $idTitularReserva,
                'nombre'         => $nombre,
                'numero_accion'  => $socio?->numero_accion,
                'tipo_usuario'   => 'SOCIO',
                'codigo_qr'      => $codigoQr->codigo,
                'estatus'        => 'COMPLETADA',
            ],
        ], 200);
    }

    private function resolverNombre(int $idUsuario, string $tipoUsuario): string
    {
        return match (strtoupper($tipoUsuario)) {
            'SOCIO'    => SocioTitular::select('nombre_completo')->find($idUsuario)?->nombre_completo ?? "Socio #{$idUsuario}",
            'FAMILIAR' => MiembrosFamiliares::select('nombre_completo')->find($idUsuario)?->nombre_completo ?? "Familiar #{$idUsuario}",
            default    => "Usuario #{$idUsuario}",
        };
    }
}
