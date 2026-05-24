<?php

namespace App\Services;

use App\Models\InscripcionClase;
use App\Models\Reservacion;
use Carbon\Carbon;

class AgendaUnificadaService
{
    /**
     * Obtiene la agenda unificada del socio en el rango de fechas indicado.
     * Combina reservaciones on-demand e inscripciones a clases, normaliza al
     * mismo formato, ordena cronológicamente y agrupa por fecha.
     *
     * @param  int    $idSocio
     * @param  string $desde   Fecha inicio en formato Y-m-d
     * @param  string $hasta   Fecha fin   en formato Y-m-d
     * @return array  { proxima_actividad: array|null, agenda: array }
     */
    public function obtenerAgenda(int $idSocio, string $desde, string $hasta): array
    {
        $items = array_merge(
            $this->obtenerReservaciones($idSocio, $desde, $hasta),
            $this->obtenerClases($idSocio, $desde, $hasta),
            $this->obtenerTorneos($idSocio, $desde, $hasta)
        );

        // Ordenar por fecha y hora_inicio ASC
        usort($items, fn($a, $b) =>
            strcmp("{$a['fecha']} {$a['hora_inicio']}", "{$b['fecha']} {$b['hora_inicio']}")
        );

        // Agrupar por fecha
        $agrupados = [];
        foreach ($items as $item) {
            $agrupados[$item['fecha']][] = $item;
        }

        $agenda = array_map(
            fn($fecha, $grupo) => ['fecha' => $fecha, 'items' => $grupo],
            array_keys($agrupados),
            array_values($agrupados)
        );

        // Próxima actividad: primer ítem cuya fecha+hora sea >= ahora (Mexico City)
        $ahora = Carbon::now('America/Mexico_City');
        $proximaActividad = collect($items)->first(function ($item) use ($ahora) {
            $dt = Carbon::parse("{$item['fecha']} {$item['hora_inicio']}", 'America/Mexico_City');
            return $dt->gte($ahora);
        });

        return [
            'proxima_actividad' => $proximaActividad,
            'agenda'            => array_values($agenda),
        ];
    }

    /**
     * Consulta las reservaciones on-demand del socio en el rango dado.
     * Estatus incluidos: ACTIVA. (Filtrado también por fecha_reserva >= hoy).
     * Eager loading: espacioFisico + disciplina (evita N+1).
     * Título: "Disciplina — Espacio".
     */
    private function obtenerReservaciones(int $idSocio, string $desde, string $hasta): array
    {
        $hoy = Carbon::today('America/Mexico_City')->toDateString();
        
        return Reservacion::where('id_socio_titular', $idSocio)
            ->whereBetween('fecha_reserva', [$desde, $hasta])
            ->where('fecha_reserva', '>=', $hoy)
            ->where('estatus_operativo', 'ACTIVA')
            ->with([
                'espacioFisico:id_espacio,nombre_espacio',
                'disciplina:id_disciplina,nombre_disciplina',
            ])
            ->get()
            ->map(fn($r) => [
                'tipo'         => 'RESERVA',
                'titulo'       => trim(
                    ($r->disciplina?->nombre_disciplina ?? 'Reservación')
                    . ($r->espacioFisico ? ' — ' . $r->espacioFisico->nombre_espacio : '')
                ),
                'fecha'        => $r->fecha_reserva,
                'hora_inicio'  => substr($r->hora_inicio, 0, 5),
                'hora_fin'     => substr($r->hora_fin, 0, 5),
                'espacio'      => $r->espacioFisico?->nombre_espacio,
                'instructor'   => null,
                'acompanantes' => is_string($r->acompanantes_draft) ? json_decode($r->acompanantes_draft, true) : ($r->acompanantes_draft ?? []),
                'estatus'      => $r->estatus_operativo,
            ])
            ->toArray();
    }

    /**
     * Consulta las inscripciones a clases del socio en el rango dado.
     * Estatus incluidos: CONFIRMADA.
     */
    private function obtenerClases(int $idSocio, string $desde, string $hasta): array
    {
        $hoy = Carbon::today('America/Mexico_City')->toDateString();

        return InscripcionClase::where('id_usuario', $idSocio)
            ->whereIn('estatus_inscripcion', ['CONFIRMADA'])
            ->whereHas('sesion', fn($q) =>
                $q->withoutGlobalScopes()
                  ->whereBetween('fecha_sesion', [$desde, $hasta])
                  ->where('fecha_sesion', '>=', $hoy)
                  ->whereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA'])
            )
            ->with([
                'sesion'                    => fn($q) => $q->withoutGlobalScopes()
                                                           ->select([
                                                               'id_sesion',
                                                               'id_actividad_plantilla',
                                                               'fecha_sesion',
                                                           ]),
                'sesion.actividadPlantilla' => fn($q) => $q->withTrashed()
                                                           ->select([
                                                               'id_actividad_plantilla',
                                                               'id_disciplina',
                                                               'id_espacio',
                                                               'id_instructor',
                                                               'hora_inicio',
                                                               'hora_fin',
                                                               'requiere_inscripcion'
                                                           ]),
                'sesion.actividadPlantilla.disciplina:id_disciplina,nombre_disciplina',
                'sesion.actividadPlantilla.espacioFisico:id_espacio,nombre_espacio',
                'sesion.actividadPlantilla.instructor:id_instructor,nombre_completo',
            ])
            ->get()
            ->map(function($i) {
                $plantilla = $i->sesion?->actividadPlantilla;
                $tipo = ($plantilla && $plantilla->requiere_inscripcion) ? 'CLASE_CERRADA' : 'CLASE_ABIERTA';

                return [
                    'tipo'        => $tipo,
                    'titulo'      => $plantilla?->disciplina?->nombre_disciplina ?? 'Clase',
                    'fecha'       => $i->sesion?->fecha_sesion,
                    'hora_inicio' => substr($plantilla?->hora_inicio ?? '', 0, 5),
                    'hora_fin'    => substr($plantilla?->hora_fin ?? '', 0, 5),
                    'espacio'     => $plantilla?->espacioFisico?->nombre_espacio,
                    'instructor'  => $plantilla?->instructor?->nombre_completo,
                    'estatus'     => $i->estatus_inscripcion,
                ];
            })
            ->toArray();
    }

    /**
     * Consulta los encuentros de torneo donde el socio o familiares participan.
     */
    private function obtenerTorneos(int $idSocio, string $desde, string $hasta): array
    {
        $hoy = Carbon::today('America/Mexico_City')->toDateString();

        // 1. Obtener IDs de familiares
        $familyIds = \App\Models\MiembrosFamiliares::where('socio_id', $idSocio)->pluck('id_miembro')->toArray();

        // 2. Obtener IDs de participantes (tabla participantes_torneo) para este socio y su familia
        $participanteIds = \App\Models\ParticipantesTorneo::where(function($q) use ($idSocio, $familyIds) {
            $q->where(function($q2) use ($idSocio) {
                $q2->where('participante_type', 'SOCIO')->where('participante_id', $idSocio);
            });
            if (!empty($familyIds)) {
                $q->orWhere(function($q2) use ($familyIds) {
                    $q2->where('participante_type', 'FAMILIAR')->whereIn('participante_id', $familyIds);
                });
            }
        })->pluck('id_participante_torneo')->toArray();

        // 3. Buscar encuentros donde el competidor 1 o 2 sea uno de esos participantes
        return \App\Models\EncuentrosTorneo::where(function($q) use ($participanteIds) {
                $q->where(function($q2) use ($participanteIds) {
                    $q2->where('competidor_1_type', 'PARTICIPANTE')->whereIn('competidor_1_id', $participanteIds);
                })->orWhere(function($q2) use ($participanteIds) {
                    $q2->where('competidor_2_type', 'PARTICIPANTE')->whereIn('competidor_2_id', $participanteIds);
                });
            })
            ->whereNotNull('fecha_hora_inicio')
            ->whereDate('fecha_hora_inicio', '>=', $hoy)
            ->whereBetween(\DB::raw('DATE(fecha_hora_inicio)'), [$desde, $hasta])
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'FINALIZADO', 'BYE'])
            ->with([
                'torneo:id_torneo,nombre_torneo',
                'espacioFisico:id_espacio,nombre_espacio'
            ])
            ->get()
            ->map(function($e) {
                $fecha = Carbon::parse($e->fecha_hora_inicio)->toDateString();
                $hora_inicio = Carbon::parse($e->fecha_hora_inicio)->format('H:i');
                $hora_fin = $e->fecha_hora_fin ? Carbon::parse($e->fecha_hora_fin)->format('H:i') : null;

                return [
                    'tipo'        => 'TORNEO',
                    'titulo'      => $e->torneo?->nombre_torneo ?? 'Torneo',
                    'fecha'       => $fecha,
                    'hora_inicio' => $hora_inicio,
                    'hora_fin'    => $hora_fin,
                    'espacio'     => $e->espacioFisico?->nombre_espacio,
                    'instructor'  => null, // Aquí podría ir el árbitro si fuera necesario
                    'estatus'     => $e->estatus_encuentro,
                    'fase'        => $e->fase_bracket
                ];
            })
            ->toArray();
    }
}
