<?php

namespace App\Http\Controllers;

use App\Models\CodigoQr;
use App\Models\EncuentrosTorneo;
use App\Models\InscripcionClase;
use App\Models\Invitados;
use App\Models\MiembrosFamiliares;
use App\Models\PasesDiarios;
use App\Models\RegistroAsistencia;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SesionInstructorController extends Controller
{
    /**
     * GET /api/v1/instructor/datos-hoy
     *
     * Retorna en un solo request las sesiones, reservaciones y encuentros del día.
     */
    public function datosHoy(): JsonResponse
    {
        $idInstructor = auth()->user()->instructor?->id_instructor;
        $hoy          = Carbon::today();

        // --- Sesiones --------------------------------------------------------
        $sesiones = [];
        if ($idInstructor) {
            $sesiones = SesionActiva::withoutGlobalScopes()
                ->where('id_instructor', $idInstructor)
                ->whereDate('fecha_sesion', $hoy)
                ->whereRaw("(fecha_sesion::date + hora_fin::time) > (NOW() - INTERVAL '15 minutes')")
                ->whereNotIn('estatus_sesion', ['CANCELADA', 'CANCELADA_POR_TORNEO'])
                ->with([
                    'disciplina:id_disciplina,nombre_disciplina',
                    'espacio:id_espacio,nombre_espacio',
                ])
                ->orderBy('hora_inicio')
                ->get()
                ->map(fn($s) => [
                    'id_sesion'            => $s->id_sesion,
                    'disciplina'           => $s->disciplina?->nombre_disciplina ?? '—',
                    'espacio'              => $s->espacio?->nombre_espacio ?? '—',
                    'hora_inicio'          => substr($s->hora_inicio ?? '', 0, 5),
                    'hora_fin'             => substr($s->hora_fin ?? '', 0, 5),
                    'cantidad_inscritos'   => (int) $s->cantidad_inscritos,
                    'cupo_maximo'          => (int) $s->cupo_maximo,
                    'requiere_inscripcion' => (bool) $s->requiere_inscripcion,
                    'estatus_sesion'       => $s->estatus_sesion,
                ])
                ->all();
        }

        // --- Reservaciones ---------------------------------------------------
        $reservaciones = Reservacion::whereDate('fecha_reserva', $hoy)
            ->whereIn('estatus_operativo', ['ACTIVA', 'COMPLETADA'])
            ->whereRaw("hora_fin::time > NOW()::time")
            ->with([
                'espacioFisico:id_espacio,nombre_espacio',
                'disciplina:id_disciplina,nombre_disciplina',
            ])
            ->orderBy('hora_inicio')
            ->get()
            ->map(function ($r) {
                $socio        = SocioTitular::select('id_socio', 'nombre_completo', 'numero_accion')->find($r->id_socio_titular);
                $acompanantes = $r->acompanantes_draft ?? [];

                return [
                    'id_reserva'        => $r->id_reserva,
                    'espacio'           => $r->espacioFisico?->nombre_espacio ?? '—',
                    'disciplina'        => $r->disciplina?->nombre_disciplina ?? '—',
                    'hora_inicio'       => substr($r->hora_inicio ?? '', 0, 5),
                    'hora_fin'          => substr($r->hora_fin ?? '', 0, 5),
                    'socio_nombre'      => $socio?->nombre_completo ?? 'Socio',
                    'numero_accion'     => $socio?->numero_accion,
                    'num_acompanantes'  => count($acompanantes),
                    'acompanantes'      => $acompanantes,
                    'estatus_operativo' => $r->estatus_operativo,
                ];
            })
            ->all();

        // --- Encuentros ------------------------------------------------------
        $encuentros = [];
        if ($idInstructor) {
            $encuentros = EncuentrosTorneo::where('id_arbitro_asignado', $idInstructor)
                ->whereDate('fecha_hora_inicio', $hoy)
                ->whereNotIn('estatus_encuentro', ['CANCELADO'])
                ->with([
                    'torneo:id_torneo,nombre_torneo,id_disciplina',
                    'torneo.disciplina:id_disciplina,nombre_disciplina',
                    'espacioFisico:id_espacio,nombre_espacio',
                ])
                ->orderBy('fecha_hora_inicio')
                ->get()
                ->map(fn($e) => [
                    'id_encuentro'      => $e->id_encuentro,
                    'torneo'            => $e->torneo?->nombre_torneo ?? '—',
                    'disciplina'        => $e->torneo?->disciplina?->nombre_disciplina ?? '—',
                    'fase_bracket'      => $e->fase_bracket,
                    'numero_encuentro'  => $e->numero_encuentro,
                    'espacio'           => $e->espacioFisico?->nombre_espacio ?? '—',
                    'hora_inicio'       => Carbon::parse($e->fecha_hora_inicio)->format('H:i'),
                    'hora_fin'          => $e->fecha_hora_fin
                        ? Carbon::parse($e->fecha_hora_fin)->format('H:i')
                        : null,
                    'estatus_encuentro' => $e->estatus_encuentro,
                    'competidor_1'      => $this->resolverNombreCompetidor($e, 1),
                    'competidor_2'      => $this->resolverNombreCompetidor($e, 2),
                ])
                ->all();
        }

        return response()->json([
            'sesiones'      => $sesiones,
            'reservaciones' => $reservaciones,
            'encuentros'    => $encuentros,
        ]);
    }

    /**
     * GET /api/v1/instructor/sesiones/{idSesion}/lista-inscriptos
     *
     * Lista de participantes inscritos en una sesión con su estado de asistencia
     * como booleano. Sirve tanto para sesiones cerradas (inscritos fijos) como
     * punto de partida para sesiones abiertas (lista vacía o previa).
     *
     * Contrato de respuesta:
     *   data: [{ tipo_usuario, id_usuario, codigo_qr, nombre, asistencia: bool }]
     *
     * Orden: pendientes (asistencia=false) primero, confirmados (true) al final.
     */
    public function listaInscriptos(Request $request, int $idSesion): JsonResponse
    {
        $sesion = SesionActiva::withoutGlobalScopes()->findOrFail($idSesion);

        $idInstructor = auth()->user()->instructor?->id_instructor;
        abort_if(
            $sesion->id_instructor !== $idInstructor,
            403,
            'No autorizado para esta sesión.'
        );

        // Inscritos no cancelados — 1 query
        $inscritos = InscripcionClase::where('id_sesion', $idSesion)
            ->whereNotIn('estatus_inscripcion', ['CANCELADA'])
            ->get();

        // Registros de asistencia existentes, pluck para O(1) lookup — 1 query
        // Valor: true = presente, false = falta registrada
        $registros = RegistroAsistencia::where('id_sesion', $idSesion)
            ->pluck('asistencia', 'id_usuario');

        // Extraer solo IDs de socios y familiares (excluyendo invitados para esta query)
        $idsSociosFamiliares = $inscritos
            ->whereIn('tipo_usuario', ['socio_titular', 'miembro_familiar'])
            ->pluck('id_usuario')
            ->unique()
            ->values();

        // Códigos QR activos de socios y familiares — 1 query
        $codigosDb = CodigoQr::whereIn('usuario_id', $idsSociosFamiliares)
            ->whereIn('tipo_usuario', ['SOCIO', 'FAMILIAR']) // Seguro para el ENUM de PostgreSQL
            ->where('estatus', 'ACTIVO')
            ->get(['codigo', 'usuario_id', 'tipo_usuario']);

        $codigos = [];
        foreach ($codigosDb as $qr) {
            $tipo = $qr->tipo_usuario === 'SOCIO' ? 'socio_titular' : 'miembro_familiar';
            $codigos[$qr->usuario_id][$tipo] = $qr->codigo;
        }

        // Para invitados: el código QR vive en la tabla invitados directamente
        // Se resuelve solo para los inscritos de tipo invitado
        $idsInvitados = $inscritos
            ->where('tipo_usuario', 'invitado')
            ->pluck('id_usuario');

        $codigosInvitados = $idsInvitados->isNotEmpty()
            ? Invitados::whereIn('id_invitado', $idsInvitados)
                ->pluck('codigo_qr', 'id_invitado')
            : collect();

        $resultado = $inscritos
            ->map(function ($inscripcion) use ($registros, $codigos, $codigosInvitados) {
                $esInvitado = strtolower($inscripcion->tipo_usuario) === 'invitado';

                $codigoQr = $esInvitado
                    ? $codigosInvitados->get($inscripcion->id_usuario)
                    : ($codigos[$inscripcion->id_usuario][$inscripcion->tipo_usuario] ?? null);

                // asistencia es booleano: true = confirmado, false = pendiente o falta
                // null del pluck se convierte a false — "sin registro" y "pendiente" son
                // indistinguibles para el frontend (ambos aparecen como no confirmados)
                $asistencia = (bool) ($registros->get($inscripcion->id_usuario, false));

                return [
                    'id_inscripcion' => $inscripcion->id_inscripcion,
                    'id_usuario'     => $inscripcion->id_usuario,
                    'tipo_usuario'   => $inscripcion->tipo_usuario,
                    'nombre'         => $this->resolverNombreInscrito(
                                            $inscripcion->id_usuario,
                                            $inscripcion->tipo_usuario
                                        ),
                    'codigo_qr'      => $codigoQr,
                    'asistencia'     => $asistencia,
                ];
            })
            // Pendientes primero, confirmados al final
            ->sortBy('asistencia')
            ->values();

        return response()->json(['data' => $resultado]);
    }

    /**
     * GET /api/v1/instructor/sesiones/{idSesion}/qr-lookup/{codigo}
     *
     * Usado exclusivamente en sesiones ABIERTAS (requiere_inscripcion=false).
     * Busca un código QR desconocido en codigos_qr (socios/familiares) y en
     * pases_diarios (invitados con prefijo QI), y retorna los datos del participante.
     *
     * Respuesta exitosa:
     *   { encontrado: true, id_usuario, tipo_usuario, nombre, codigo_qr }
     *
     * Respuesta no encontrado:
     *   { encontrado: false, message } — HTTP 404
     */
    public function qrLookup(Request $request, int $idSesion, string $codigo): JsonResponse
    {
        $sesion = SesionActiva::withoutGlobalScopes()->findOrFail($idSesion);

        $idInstructor = auth()->user()->instructor?->id_instructor;
        abort_if(
            $sesion->id_instructor !== $idInstructor,
            403,
            'No autorizado para esta sesión.'
        );

        // Solo aplica a sesiones abiertas
        if ($sesion->requiere_inscripcion) {
            return response()->json([
                'message' => 'Esta operación solo aplica a sesiones abiertas.',
            ], 400);
        }

        $codigoNormalizado = strtoupper(trim($codigo));

        // Validar formato QR: QS (socio), MF (familiar), QI (invitado) + 6 alfanuméricos
        if (!preg_match('/^(QS|MF|QI)[A-Z0-9]{6}$/', $codigoNormalizado)) {
            return response()->json([
                'encontrado' => false,
                'message'    => 'Formato de código QR inválido.',
            ], 422);
        }

        // ── Socios y Familiares: buscar en codigos_qr ────────────────────────
        if (str_starts_with($codigoNormalizado, 'QS') || str_starts_with($codigoNormalizado, 'MF')) {
            $codigoQr = CodigoQr::where('codigo', $codigoNormalizado)
                ->where('estatus', 'ACTIVO')
                ->first();

            if ($codigoQr) {
                return response()->json([
                    'encontrado'   => true,
                    'id_usuario'   => $codigoQr->usuario_id,
                    'tipo_usuario' => strtolower($codigoQr->tipo_usuario) === 'socio'
                                        ? 'socio_titular'
                                        : 'miembro_familiar',
                    'nombre'       => $this->resolverNombreInscrito(
                                        $codigoQr->usuario_id,
                                        $codigoQr->tipo_usuario
                                    ),
                    'codigo_qr'    => $codigoNormalizado,
                ]);
            }

            return response()->json([
                'encontrado' => false,
                'message'    => 'Código QR inválido, inactivo o no registrado.',
            ], 404);
        }

        // ── Invitados (QI): buscar en invitados + pases_diarios activos ──────
        if (str_starts_with($codigoNormalizado, 'QI')) {
            $invitado = Invitados::where('codigo_qr', $codigoNormalizado)
                ->whereNull('deleted_at')
                ->first();

            if (!$invitado) {
                return response()->json([
                    'encontrado' => false,
                    'message'    => 'Código de invitado no encontrado.',
                ], 404);
            }

            // Verificar que el pase diario esté activo y vigente
            $paseActivo = PasesDiarios::where('invitado_id', $invitado->id_invitado)
                ->where('estatus_acceso', 'ACTIVO')
                ->where('fecha_expiracion', '>', now())
                ->exists();

            if (!$paseActivo) {
                return response()->json([
                    'encontrado' => false,
                    'message'    => 'El pase de invitado está expirado o inactivo.',
                ], 404);
            }

            return response()->json([
                'encontrado'   => true,
                'id_usuario'   => $invitado->id_invitado,
                'tipo_usuario' => 'invitado',
                'nombre'       => $invitado->nombre_invitado,
                'codigo_qr'    => $codigoNormalizado,
            ]);
        }

        return response()->json(['encontrado' => false, 'message' => 'Código no reconocido.'], 404);
    }

    /**
     * POST /api/v1/instructor/sesiones/{idSesion}/confirmar-asistencia
     *
     * Recibe el array completo de participantes confirmados, crea los registros
     * de asistencia en una sola transacción con deduplicación por (id_sesion, id_usuario),
     * actualiza las inscripciones a ASISTIO y marca lista_asistencia_enviada = true.
     *
     * Body:
     *   confirmados: [{ id_usuario: int, tipo_usuario: string, codigo_qr: string }]
     *   metodo: 'ESCANER_QR' | 'INGRESO_MANUAL'
     *
     * Respuestas:
     *   200 — todos registrados correctamente
     *   207 — registro parcial (algunos QR fallaron)
     *   409 — la sesión ya no está EN_CURSO o la lista ya fue enviada
     */
    public function confirmarAsistencia(Request $request, int $idSesion): JsonResponse
    {
        $request->validate([
            'confirmados'               => ['required', 'array', 'min:1'],
            'confirmados.*.id_usuario'  => ['required', 'integer'],
            'confirmados.*.tipo_usuario'=> ['required', 'string'],
            'confirmados.*.codigo_qr'   => ['required', 'string'],
            'metodo'                    => ['required', 'in:ESCANER_QR,INGRESO_MANUAL'],
        ]);

        $sesion = SesionActiva::withoutGlobalScopes()->findOrFail($idSesion);

        $idInstructor = auth()->user()->instructor?->id_instructor;
        abort_if(
            $sesion->id_instructor !== $idInstructor,
            403,
            'No autorizado para esta sesión.'
        );

        // Guardia: la sesión debe estar EN_CURSO para aceptar asistencias
        if ($sesion->estatus_sesion !== 'EN_CURSO') {
            return response()->json([
                'success' => false,
                'message' => "La sesión ya no está en curso (estatus actual: {$sesion->estatus_sesion}).",
                'codigo'  => 'SESION_NO_EN_CURSO',
            ], 409);
        }


        return DB::transaction(function () use ($request, $sesion) {
            $erroresParciales = [];
            $registrados      = 0;

            // Cargar usuarios ya con registro positivo para deduplicación en memoria
            $yaRegistrados = RegistroAsistencia::where('id_sesion', $sesion->id_sesion)
                ->where('asistencia', true)
                ->pluck('id_usuario')
                ->flip(); // flip → O(1) lookup

            foreach ($request->confirmados as $confirmado) {
                $idUsuario   = (int) $confirmado['id_usuario'];
                $tipoUsuario = $this->normalizarTipoUsuarioAsistencia($confirmado['tipo_usuario']);
                $codigoQr    = strtoupper($confirmado['codigo_qr']);

                // Skip silencioso para duplicados — idempotencia a nivel de fila
                if ($yaRegistrados->has($idUsuario)) {
                    continue;
                }

                // Savepoint por iteración: en Postgres, si un INSERT falla dentro de
                // una transacción, ésta queda abortada y cualquier statement posterior
                // dispara SQLSTATE 25P02. DB::transaction anidado crea SAVEPOINTs que
                // permiten hacer rollback parcial y continuar con la transacción padre.
                try {
                    DB::transaction(function () use ($sesion, $idUsuario, $tipoUsuario, $request) {
                        RegistroAsistencia::create([
                            'id_sesion'           => $sesion->id_sesion,
                            'id_usuario'          => $idUsuario,
                            'tipo_usuario'        => $tipoUsuario,
                            'asistencia'          => true,
                            'metodo_registro'     => $request->metodo === 'INGRESO_MANUAL' ? 'MANUAL' : $request->metodo,
                            'fecha_hora_registro' => now(),
                        ]);

                        // Actualizar inscripción a ASISTIO si existe (sesiones cerradas)
                        InscripcionClase::where('id_sesion', $sesion->id_sesion)
                            ->where('id_usuario', $idUsuario)
                            ->whereIn('estatus_inscripcion', ['CONFIRMADA'])
                            ->update(['estatus_inscripcion' => 'ASISTIO']);
                    });

                    $yaRegistrados->put($idUsuario, true);
                    $registrados++;
                } catch (\Throwable $e) {
                    \Log::warning('confirmarAsistencia: fallo en registro individual', [
                        'id_sesion'  => $sesion->id_sesion,
                        'id_usuario' => $idUsuario,
                        'codigo_qr'  => $codigoQr,
                        'error'      => $e->getMessage(),
                    ]);
                    $erroresParciales[] = $codigoQr;
                }
            }

            // Marcar la lista como enviada independientemente de errores parciales
            $sesion->update(['lista_asistencia_enviada' => true]);

            if (!empty($erroresParciales)) {
                return response()->json([
                    'success'           => false,
                    'message'           => "Se registraron {$registrados} participante(s), pero hubo errores en algunos códigos.",
                    'total_registrados' => $registrados,
                    'errores_parciales' => $erroresParciales,
                ], 207);
            }

            return response()->json([
                'success'           => true,
                'message'           => "Asistencia confirmada. {$registrados} participante(s) registrado(s).",
                'total_registrados' => $registrados,
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    /**
     * Normaliza tipo_usuario al enum_tipo_usuario_registro_asistencia
     * (SOCIO_TITULAR | MIEMBRO_FAMILIAR | INVITADO). El frontend envía
     * los valores en minúsculas del enum_tipo_usuario.
     */
    private function normalizarTipoUsuarioAsistencia(string $tipoUsuario): string
    {
        return match (strtolower($tipoUsuario)) {
            'socio_titular', 'socio'        => 'SOCIO_TITULAR',
            'miembro_familiar', 'familiar'  => 'MIEMBRO_FAMILIAR',
            'invitado'                      => 'INVITADO',
            default                         => strtoupper($tipoUsuario),
        };
    }

    private function resolverNombreInscrito(int $idUsuario, string $tipoUsuario): string
    {
        return match (strtolower($tipoUsuario)) {
            'socio_titular', 'socio' => SocioTitular::select('nombre_completo')
                                            ->find($idUsuario)
                                            ?->nombre_completo ?? "Socio #{$idUsuario}",
            'miembro_familiar', 'familiar' => MiembrosFamiliares::select('nombre_completo')
                                                ->find($idUsuario)
                                                ?->nombre_completo ?? "Familiar #{$idUsuario}",
            'invitado'               => Invitados::select('nombre_invitado')
                                            ->find($idUsuario)
                                            ?->nombre_invitado ?? "Invitado #{$idUsuario}",
            default                  => "Usuario #{$idUsuario}",
        };
    }

    private function resolverNombreCompetidor(EncuentrosTorneo $e, int $num): string
    {
        $id   = $num === 1 ? $e->competidor_1_id   : $e->competidor_2_id;
        $type = $num === 1 ? $e->competidor_1_type  : $e->competidor_2_type;

        if (!$id) return 'Por definir';

        if (str_contains(strtoupper($type ?? ''), 'SOCIO') || str_contains(strtoupper($type ?? ''), 'PARTICIPANTE')) {
            $socio = SocioTitular::select('id_socio', 'nombre_completo')->find($id);
            return $socio?->nombre_completo ?? "Competidor #{$id}";
        }

        $equipo = \App\Models\EquipoTorneo::select('id_equipo', 'nombre_equipo')->find($id);
        return $equipo?->nombre_equipo ?? "Equipo #{$id}";
    }
}
