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
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SesionInstructorController extends Controller
{
    /**
     * GET /api/v1/instructor/datos-hoy
     *
     * Retorna en un solo request las sesiones, reservaciones y encuentros del día.
     */
    public function datosHoy(): JsonResponse
    {
        $idInstructor = $this->idInstructorActual();
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
     * GET /api/v1/instructor/historial-sesiones
     * Historial de sesiones del instructor con filtros y métricas agregadas.
     */
    public function historialSesiones(Request $request): JsonResponse
    {
        // 1. Obtener ID del instructor
        $idInstructor = $this->idInstructorActual();
        if (!$idInstructor) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene un perfil de instructor válido.',
            ], 403);
        }

        // 2. Validación de parámetros de búsqueda
        $validator = Validator::make($request->all(), [
            'filtro_estatus' => ['nullable', 'string', 'in:completadas,canceladas,todas'],
            'fecha_desde'    => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
            'id_disciplina'  => ['nullable', 'integer'],
            'per_page'       => ['nullable', 'integer', 'min:5', 'max:100'],
        ], [
            'fecha_hasta.after_or_equal' => 'El rango de fechas es inválido: fecha_desde no puede ser mayor que fecha_hasta.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación en los parámetros de búsqueda.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $filtroEstatus = $request->query('filtro_estatus', 'todas');
        $fechaDesde    = $request->query('fecha_desde');
        $fechaHasta    = $request->query('fecha_hasta');
        $idDisciplina  = $request->query('id_disciplina');
        $perPage       = (int) $request->query('per_page', 15);
        $hoy           = Carbon::today()->toDateString();

        // 3. Construir consulta con SesionActiva::withoutGlobalScopes()
        $query = SesionActiva::withoutGlobalScopes()
            ->where('id_instructor', $idInstructor);

        if ($filtroEstatus === 'completadas') {
            $query->where(function ($q) use ($hoy) {
                $q->whereIn(DB::raw('estatus_sesion::text'), ['FINALIZADA', 'COMPLETADA'])
                  ->orWhere(function ($sub) use ($hoy) {
                      $sub->whereNotIn(DB::raw('estatus_sesion::text'), ['CANCELADA', 'CANCELADA_POR_TORNEO', 'CANCELADA_POR_LLUVIA'])
                          ->whereDate('fecha_sesion', '<', $hoy);
                  });
            });
        } elseif ($filtroEstatus === 'canceladas') {
            $query->whereIn(DB::raw('estatus_sesion::text'), ['CANCELADA', 'CANCELADA_POR_TORNEO', 'CANCELADA_POR_LLUVIA']);
        } else {
            // 'todas': completadas, canceladas o pasadas
            $query->where(function ($q) use ($hoy) {
                $q->whereIn(DB::raw('estatus_sesion::text'), ['FINALIZADA', 'COMPLETADA', 'CANCELADA', 'CANCELADA_POR_TORNEO', 'CANCELADA_POR_LLUVIA'])
                  ->orWhereDate('fecha_sesion', '<', $hoy);
            });
        }

        // Filtro de fechas
        if ($fechaDesde) {
            $query->whereDate('fecha_sesion', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('fecha_sesion', '<=', $fechaHasta);
        } else {
            $query->whereDate('fecha_sesion', '<=', $hoy);
        }

        // Filtro de disciplina
        if ($idDisciplina) {
            $query->where('id_disciplina', $idDisciplina);
        }

        // Eager loading y conteo de asistencias confirmadas
        $query->with([
            'disciplina:id_disciplina,nombre_disciplina',
            'espacio:id_espacio,nombre_espacio',
        ])->withCount([
            'registroAsistencias as asistencias_registros' => function ($q) {
                $q->where('asistencia', true);
            },
            'inscripcionesClase as asistencias_inscripciones' => function ($q) {
                $q->where('estatus_inscripcion', 'ASISTIO');
            },
        ]);

        // Ordenar descendentemente por fecha y hora de inicio, luego paginar
        $sesiones = $query->orderByDesc('fecha_sesion')
            ->orderByDesc('hora_inicio')
            ->paginate($perPage);

        // Formatear datos de respuesta
        $data = $sesiones->getCollection()->map(function ($s) use ($hoy) {
            $estatus     = $s->estatus_sesion;
            $esCancelada = in_array($estatus, ['CANCELADA', 'CANCELADA_POR_TORNEO', 'CANCELADA_POR_LLUVIA']);

            if ($esCancelada) {
                $estatusRespuesta = 'CANCELADA';
            } elseif ($estatus === 'FINALIZADA' || $estatus === 'COMPLETADA' || $s->fecha_sesion < $hoy) {
                $estatusRespuesta = 'COMPLETADA';
            } else {
                $estatusRespuesta = $estatus;
            }

            $asistencias = max((int) ($s->asistencias_registros ?? 0), (int) ($s->asistencias_inscripciones ?? 0));

            return [
                'id_sesion'               => (int) $s->id_sesion,
                'disciplina'              => $s->disciplina?->nombre_disciplina ?? '—',
                'espacio'                 => $s->espacio?->nombre_espacio ?? '—',
                'fecha_sesion'            => $s->fecha_sesion ? Carbon::parse($s->fecha_sesion)->format('Y-m-d') : null,
                'hora_inicio'             => substr($s->hora_inicio ?? '', 0, 5),
                'hora_fin'                => substr($s->hora_fin ?? '', 0, 5),
                'cantidad_inscritos'      => (int) $s->cantidad_inscritos,
                'asistencias_confirmadas' => $asistencias,
                'cupo_maximo'             => (int) $s->cupo_maximo,
                'requiere_inscripcion'    => (bool) $s->requiere_inscripcion,
                'estatus_sesion'          => $estatusRespuesta,
                'motivo_cancelacion'      => $esCancelada ? ($s->motivo_cancelacion ?? null) : null,
            ];
        })->values();

        // 4. Métricas agregadas para nodo meta
        $metricsBase = SesionActiva::withoutGlobalScopes()
            ->where('id_instructor', $idInstructor);

        if ($fechaDesde) {
            $metricsBase->whereDate('fecha_sesion', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $metricsBase->whereDate('fecha_sesion', '<=', $fechaHasta);
        } else {
            $metricsBase->whereDate('fecha_sesion', '<=', $hoy);
        }

        if ($idDisciplina) {
            $metricsBase->where('id_disciplina', $idDisciplina);
        }

        $totalCompletadas = (clone $metricsBase)->where(function ($q) use ($hoy) {
            $q->whereIn(DB::raw('estatus_sesion::text'), ['FINALIZADA', 'COMPLETADA'])
              ->orWhere(function ($sub) use ($hoy) {
                  $sub->whereNotIn(DB::raw('estatus_sesion::text'), ['CANCELADA', 'CANCELADA_POR_TORNEO', 'CANCELADA_POR_LLUVIA'])
                      ->whereDate('fecha_sesion', '<', $hoy);
              });
        })->count();

        $totalCanceladas = (clone $metricsBase)
            ->whereIn(DB::raw('estatus_sesion::text'), ['CANCELADA', 'CANCELADA_POR_TORNEO', 'CANCELADA_POR_LLUVIA'])
            ->count();

        $idsCompletadas = (clone $metricsBase)->where(function ($q) use ($hoy) {
            $q->whereIn(DB::raw('estatus_sesion::text'), ['FINALIZADA', 'COMPLETADA'])
              ->orWhere(function ($sub) use ($hoy) {
                  $sub->whereNotIn(DB::raw('estatus_sesion::text'), ['CANCELADA', 'CANCELADA_POR_TORNEO', 'CANCELADA_POR_LLUVIA'])
                      ->whereDate('fecha_sesion', '<', $hoy);
              });
        })->pluck('id_sesion');

        $totalAlumnosAtendidos = 0;
        if ($idsCompletadas->isNotEmpty()) {
            $sesionesConAsistencia = SesionActiva::withoutGlobalScopes()
                ->whereIn('id_sesion', $idsCompletadas)
                ->withCount([
                    'registroAsistencias as asistencias_registros' => fn($q) => $q->where('asistencia', true),
                    'inscripcionesClase as asistencias_inscripciones' => fn($q) => $q->where('estatus_inscripcion', 'ASISTIO'),
                ])
                ->get();

            $totalAlumnosAtendidos = $sesionesConAsistencia->sum(function ($s) {
                return max((int) ($s->asistencias_registros ?? 0), (int) ($s->asistencias_inscripciones ?? 0));
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total_sesiones_completadas' => $totalCompletadas,
                'total_sesiones_canceladas'  => $totalCanceladas,
                'total_alumnos_atendidos'    => $totalAlumnosAtendidos,
                'current_page'               => $sesiones->currentPage(),
                'last_page'                  => $sesiones->lastPage(),
                'total'                      => $sesiones->total(),
            ],
        ], 200);
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

        $idInstructor = $this->idInstructorActual();
        abort_if(
            $sesion->id_instructor !== $idInstructor,
            403,
            'No autorizado para esta sesión.'
        );

        // Inscritos no cancelados — 1 query
        $inscritos = InscripcionClase::where('id_sesion', $idSesion)
            ->whereNotIn('estatus_inscripcion', ['CANCELADA'])
            ->get();

        // Registros de asistencia existentes — clave compuesta tipo:id para no
        // confundir un socio_titular con id=N y un miembro_familiar con id=N.
        // Valor: true = presente, false = falta registrada.
        $registrosRaw = RegistroAsistencia::where('id_sesion', $idSesion)
            ->get(['id_usuario', 'tipo_usuario', 'asistencia']);

        // Normalizar tipo_usuario del registro (MAYUSCULAS) al snake_case de inscripciones
        $registros = $registrosRaw->mapWithKeys(function ($r) {
            $tipoNorm = match (strtoupper($r->tipo_usuario)) {
                'SOCIO_TITULAR'    => 'socio_titular',
                'MIEMBRO_FAMILIAR' => 'miembro_familiar',
                default            => strtolower($r->tipo_usuario),
            };
            return ["{$tipoNorm}:{$r->id_usuario}" => (bool) $r->asistencia];
        });

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

                // Lookup por clave compuesta tipo:id para independencia entre tipos
                $claveAsistencia = "{$inscripcion->tipo_usuario}:{$inscripcion->id_usuario}";
                $asistencia = (bool) ($registros->get($claveAsistencia, false));

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
                    'foto_perfil'    => $this->resolverFotoPerfil($inscripcion->id_usuario, $inscripcion->tipo_usuario),
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

        $idInstructor = $this->idInstructorActual();
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
                    'foto_perfil'  => $this->resolverFotoPerfil($codigoQr->usuario_id, $codigoQr->tipo_usuario),
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
                'foto_perfil'  => null,
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
            'confirmados.*.codigo_qr'   => ['nullable', 'string'],
            'metodo'                    => ['required', 'in:ESCANER_QR,MANUAL,SIN_REGISTRO'],
        ]);

        $sesion = SesionActiva::withoutGlobalScopes()->find($idSesion);
        if (!$sesion) {
            return response()->json([
                'success' => false,
                'message' => "La sesión #{$idSesion} no fue encontrada en la base de datos.",
            ], 404);
        }

        $idInstructor = $this->idInstructorActual();
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
            $noShowsRegistrados = 0;

            // Cargar pares (id_usuario, tipo_usuario) ya registrados para deduplicación.
            $yaRegistrados = RegistroAsistencia::where('id_sesion', $sesion->id_sesion)
                ->where('asistencia', true)
                ->get(['id_usuario', 'tipo_usuario'])
                ->mapWithKeys(fn($r) => [strtoupper($r->tipo_usuario) . ":{$r->id_usuario}" => true]);

            foreach ($request->confirmados as $confirmado) {
                $idUsuario       = (int) $confirmado['id_usuario'];
                $tipoRegistro    = $this->normalizarTipoUsuarioAsistencia($confirmado['tipo_usuario']);
                $tipoInscripcion = strtolower($confirmado['tipo_usuario']);
                $codigoQr        = strtoupper($confirmado['codigo_qr'] ?? '');
                $claveUnica      = "{$tipoRegistro}:{$idUsuario}";

                if ($yaRegistrados->has($claveUnica)) {
                    continue;
                }

                try {
                    DB::transaction(function () use ($sesion, $idUsuario, $tipoRegistro, $tipoInscripcion, $request) {
                        RegistroAsistencia::create([
                            'id_sesion'           => $sesion->id_sesion,
                            'id_usuario'          => $idUsuario,
                            'tipo_usuario'        => $tipoRegistro,
                            'asistencia'          => true,
                            'metodo_registro'     => $request->metodo,
                            'fecha_hora_registro' => now(),
                        ]);

                        DB::statement(
                            'UPDATE inscripciones_clases
                             SET estatus_inscripcion = ?
                             WHERE id_sesion = ?
                               AND id_usuario = ?
                               AND tipo_usuario = ?
                               AND estatus_inscripcion IN (?, ?)',
                            ['ASISTIO', $sesion->id_sesion, $idUsuario, $tipoInscripcion, 'CONFIRMADA', 'LISTA_ESPERA']
                        );
                    });

                    $yaRegistrados->put($claveUnica, true);
                    $registrados++;
                } catch (\Throwable $e) {
                    Log::warning('confirmarAsistencia: fallo en registro individual', [
                        'id_sesion'  => $sesion->id_sesion,
                        'id_usuario' => $idUsuario,
                        'codigo_qr'  => $codigoQr,
                        'error'      => $e->getMessage(),
                    ]);
                    $erroresParciales[] = $codigoQr;
                }
            }

            if ($request->boolean('cerrar_lista')) {
                $pendientes = InscripcionClase::where('id_sesion', $sesion->id_sesion)
                    ->whereIn('estatus_inscripcion', ['CONFIRMADA', 'LISTA_ESPERA'])
                    ->whereNotIn('tipo_usuario', ['invitado'])
                    ->get();

                foreach ($pendientes as $inscripcion) {
                    $clavePendiente = $this->normalizarTipoUsuarioAsistencia($inscripcion->tipo_usuario) . ":{$inscripcion->id_usuario}";
                    if ($yaRegistrados->has($clavePendiente)) {
                        continue;
                    }

                    try {
                        RegistroAsistencia::create([
                            'id_sesion'           => $sesion->id_sesion,
                            'id_usuario'          => $inscripcion->id_usuario,
                            'tipo_usuario'        => $this->normalizarTipoUsuarioAsistencia($inscripcion->tipo_usuario),
                            'asistencia'          => false,
                            'metodo_registro'     => 'SIN_REGISTRO',
                            'fecha_hora_registro' => now(),
                        ]);

                        $inscripcion->update(['estatus_inscripcion' => 'FALTA']);

                        if (in_array(strtolower((string) $inscripcion->tipo_usuario), ['socio_titular', 'socio'], true)) {
                            SocioTitular::find($inscripcion->id_usuario)?->increment('contador_no_shows');
                        } elseif (in_array(strtolower((string) $inscripcion->tipo_usuario), ['miembro_familiar', 'familiar'], true)) {
                            $familiar = MiembrosFamiliares::find($inscripcion->id_usuario);
                            if ($familiar) {
                                $familiar->increment('contador_no_shows');
                                if ($familiar->socio_id) {
                                    SocioTitular::find($familiar->socio_id)?->increment('contador_no_shows');
                                }
                            }
                        }

                        \App\Jobs\ProcesarAlertaNoShowJob::dispatch($inscripcion);
                        $noShowsRegistrados++;
                    } catch (\Throwable $e) {
                        Log::warning("confirmarAsistencia: error marcando no-show para inscripcion #{$inscripcion->id_inscripcion}: {$e->getMessage()}");
                    }
                }
            }

            if ($registrados > 0 || $noShowsRegistrados > 0) {
                $sesion->update(['lista_asistencia_enviada' => true]);
            }

            if (!empty($erroresParciales)) {
                return response()->json([
                    'success'           => false,
                    'message'           => "Se registraron {$registrados} participante(s), pero hubo errores en algunos códigos.",
                    'total_registrados' => $registrados,
                    'total_no_shows'    => $noShowsRegistrados,
                    'errores_parciales' => $erroresParciales,
                ], 207);
            }

            return response()->json([
                'success'           => true,
                'message'           => "Asistencia procesada. {$registrados} asistencias confirmadas, {$noShowsRegistrados} no-shows registrados.",
                'total_registrados' => $registrados,
                'total_no_shows'    => $noShowsRegistrados,
            ]);
        });
    }

    /**
     * POST /api/v1/instructor/sesiones/{idSesion}/marcar-no-show
     *
     * Registra inasistencia (No-Show) para un participante o para los pendientes
     * al cerrar la lista, emitiendo la alerta correspondiente al socio titular.
     */
    public function marcarNoShow(Request $request, int $idSesion): JsonResponse
    {
        $sesion = SesionActiva::withoutGlobalScopes()->find($idSesion);
        if (!$sesion) {
            return response()->json([
                'success' => false,
                'message' => "La sesión #{$idSesion} no fue encontrada en la base de datos.",
            ], 404);
        }

        $idInstructor = $this->idInstructorActual();
        abort_if(
            $sesion->id_instructor !== $idInstructor,
            403,
            'No autorizado para esta sesión.'
        );

        $idUsuario   = $request->input('id_usuario');
        $tipoUsuario = $request->input('tipo_usuario', 'socio_titular');

        return DB::transaction(function () use ($request, $sesion, $idUsuario, $tipoUsuario) {
            $query = InscripcionClase::where('id_sesion', $sesion->id_sesion)
                ->whereIn('estatus_inscripcion', ['CONFIRMADA', 'LISTA_ESPERA']);

            if ($idUsuario) {
                $query->where('id_usuario', (int) $idUsuario);
                if ($tipoUsuario) {
                    $query->where('tipo_usuario', strtolower((string) $tipoUsuario));
                }
            }

            $inscripciones = $query->get();

            if ($inscripciones->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron inscripciones pendientes para marcar como No-Show en esta sesión.',
                ], 404);
            }

            $procesados = 0;
            foreach ($inscripciones as $inscripcion) {
                $tipoRegistro = $this->normalizarTipoUsuarioAsistencia($inscripcion->tipo_usuario);

                RegistroAsistencia::updateOrCreate(
                    [
                        'id_sesion'    => $sesion->id_sesion,
                        'id_usuario'   => $inscripcion->id_usuario,
                        'tipo_usuario' => $tipoRegistro,
                    ],
                    [
                        'asistencia'          => false,
                        'metodo_registro'     => $request->input('metodo', 'MANUAL'),
                        'fecha_hora_registro' => now(),
                    ]
                );

                $inscripcion->update(['estatus_inscripcion' => 'FALTA']);

                if (in_array(strtolower((string) $inscripcion->tipo_usuario), ['socio_titular', 'socio'], true)) {
                    SocioTitular::find($inscripcion->id_usuario)?->increment('contador_no_shows');
                } elseif (in_array(strtolower((string) $inscripcion->tipo_usuario), ['miembro_familiar', 'familiar'], true)) {
                    $familiar = MiembrosFamiliares::find($inscripcion->id_usuario);
                    if ($familiar) {
                        $familiar->increment('contador_no_shows');
                        if ($familiar->socio_id) {
                            SocioTitular::find($familiar->socio_id)?->increment('contador_no_shows');
                        }
                    }
                }

                \App\Jobs\ProcesarAlertaNoShowJob::dispatch($inscripcion);
                $procesados++;
            }

            $sesion->update(['lista_asistencia_enviada' => true]);

            return response()->json([
                'success'        => true,
                'message'        => "Se registraron {$procesados} inasistencia(s) y se emitieron las alertas de No-Show.",
                'total_no_shows' => $procesados,
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    /** @return int|null id_instructor del usuario autenticado */
    private function idInstructorActual(): ?int
    {
        /** @var User|null $user */
        $user = auth()->user();
        return $user?->instructor?->id_instructor;
    }

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

    private function resolverFotoPerfil(int $idUsuario, string $tipoUsuario): ?string
    {
        if (strtolower($tipoUsuario) === 'socio_titular' || strtolower($tipoUsuario) === 'socio') {
            return (string) cloudinary()->image("socios/profiles/socio_{$idUsuario}")->version(time())->toUrl();
        }
        return null;
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
