<?php

namespace App\Services;

use App\Models\SesionActiva;
use App\Models\EncuentrosTorneo;
use App\Models\TurnosLudoteca;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InstructorAgendaService
{
    /**
     * Agenda unificada del instructor: sesiones asignadas + encuentros como árbitro.
     * Agrupadas por fecha, ordenadas cronológicamente.
     *
     * @param  int    $idInstructor  PK en tabla instructores (= user_id del token)
     * @param  string $desde         Y-m-d
     * @param  string $hasta         Y-m-d
     */
    public function obtenerAgenda(int $idInstructor, string $desde, string $hasta): array
    {
        $items = [
            ...$this->obtenerSesiones($idInstructor, $desde, $hasta),
            ...$this->obtenerTorneos($idInstructor, $desde, $hasta),
            ...$this->obtenerTurnosLudoteca($idInstructor, $desde, $hasta),
        ];

        usort($items, fn($a, $b) =>
            strcmp("{$a['fecha']} {$a['hora_inicio']}", "{$b['fecha']} {$b['hora_inicio']}")
        );

        $agrupados = [];
        foreach ($items as $item) {
            $agrupados[$item['fecha']][] = $item;
        }

        $agenda = array_map(
            fn($fecha, $grupo) => ['fecha' => $fecha, 'items' => $grupo],
            array_keys($agrupados),
            array_values($agrupados)
        );

        $ahora = Carbon::now('America/Mexico_City');
        $proximaActividad = collect($items)->first(
            fn($item) => Carbon::parse("{$item['fecha']} {$item['hora_inicio']}", 'America/Mexico_City')->gte($ahora)
        );

        return [
            'proxima_actividad' => $proximaActividad,
            'agenda'            => array_values($agenda),
        ];
    }

    /**
     * Sesiones activas en el rango cuyo instructor principal (o sustituto) sea
     * el instructor autenticado. Devuelve la lista de inscritos agrupada por estado.
     *
     * Para CLASE_ABIERTA  → grupos: inscritos, asistencia
     * Para CLASE_CERRADA  → grupos: inscritos, asistencia, no_show
     */
    private function obtenerSesiones(int $idInstructor, string $desde, string $hasta): array
    {
        $hoy = Carbon::today('America/Mexico_City')->toDateString();

        // Sesiones donde el instructor es el titular de la actividad plantilla
        // (dentro de una plantilla publicada) O el instructor sustituto de la sesión.
        $sesiones = SesionActiva::withoutGlobalScopes()
            ->whereBetween('fecha_sesion', [$desde, $hasta])
            ->where('fecha_sesion', '>=', $hoy)
            ->where(function ($q) use ($hoy) {
                // Hoy: mostrar todas independientemente del estatus
                // Días futuros: excluir canceladas y finalizadas
                $q->where('fecha_sesion', $hoy)
                  ->orWhereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA']);
            })
            ->where('id_instructor', $idInstructor)
            ->whereHas('actividadPlantilla.plantilla', fn($p) =>
                $p->where('publicada', true)
                  ->whereColumn('plantillas_programacion.fecha_inicio', '<=', 'sesiones_activas.fecha_sesion')
                  ->whereColumn('plantillas_programacion.fecha_fin',    '>=', 'sesiones_activas.fecha_sesion')
            )
            ->with([
                'disciplina:id_disciplina,nombre_disciplina',
                'espacio:id_espacio,nombre_espacio',
                'inscripcionesClase' => fn($q) => $q->whereIn('estatus_inscripcion', [
                    'CONFIRMADA', 'ASISTIO', 'FALTA',
                ])->with('socio:id_socio,nombre_completo'),
            ])
            ->get();

        return $sesiones->map(function ($sesion) {
            $esCerrada  = (bool) $sesion->requiere_inscripcion;
            $tipo       = $esCerrada ? 'CLASE_CERRADA' : 'CLASE_ABIERTA';

            $inscritos  = $sesion->inscripcionesClase->where('estatus_inscripcion', 'CONFIRMADA');
            $asistencia = $sesion->inscripcionesClase->where('estatus_inscripcion', 'ASISTIO');
            $noShow     = $sesion->inscripcionesClase->where('estatus_inscripcion', 'FALTA');

            $mapUsuario = fn($ins) => [
                'id_inscripcion' => $ins->id_inscripcion,
                'nombre'         => $ins->socio?->nombre_completo ?? 'Socio',
                'estatus'        => $ins->estatus_inscripcion,
            ];

            $grupos = [
                'inscritos'  => $inscritos->map($mapUsuario)->values()->toArray(),
                'asistencia' => $asistencia->map($mapUsuario)->values()->toArray(),
            ];

            if ($esCerrada) {
                $grupos['falta'] = $noShow->map($mapUsuario)->values()->toArray();
            }

            return [
                'id_sesion'   => $sesion->id_sesion,
                'tipo'        => $tipo,
                'disciplina'  => $sesion->disciplina?->nombre_disciplina,
                'titulo'      => $sesion->disciplina?->nombre_disciplina ?? 'Sesión',
                'fecha'       => $sesion->fecha_sesion,
                'hora_inicio' => substr($sesion->hora_inicio ?? '', 0, 5),
                'hora_fin'    => substr($sesion->hora_fin ?? '', 0, 5),
                'espacio'     => $sesion->espacio?->nombre_espacio,
                'cupo_maximo' => $sesion->cupo_maximo,
                'estatus'     => $sesion->estatus_sesion,
                'contadores'  => [
                    'inscritos'  => $inscritos->count() + $asistencia->count() + $noShow->count(),
                    'asistencia' => $asistencia->count(),
                    'falta'      => $noShow->count(),
                ],
                'listas'      => $grupos,
            ];
        })->toArray();
    }

    /**
     * Turnos de ludoteca asignados al instructor en el rango de fechas.
     */
    private function obtenerTurnosLudoteca(int $idInstructor, string $desde, string $hasta): array
    {
        $hoy = Carbon::today('America/Mexico_City')->toDateString();

        return TurnosLudoteca::where('id_instructor', $idInstructor)
            ->whereBetween('fecha', [$desde, $hasta])
            ->where('fecha', '>=', $hoy)
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get()
            ->map(fn($turno) => [
                'id_turno'    => $turno->id_turno,
                'tipo'        => 'TURNO_LUDOTECA',
                'titulo'      => 'Turno Ludoteca',
                'fecha'       => $turno->fecha,
                'hora_inicio' => substr($turno->hora_inicio, 0, 5),
                'hora_fin'    => substr($turno->hora_fin, 0, 5),
                'espacio'     => null,
                'estatus'     => 'PROGRAMADO',
                'contadores'  => null,
                'listas'      => null,
            ])
            ->toArray();
    }

    /**
     * Encuentros de torneo donde el instructor es el árbitro asignado.
     */
    private function obtenerTorneos(int $idInstructor, string $desde, string $hasta): array
    {
        $hoy = Carbon::today('America/Mexico_City')->toDateString();

        return EncuentrosTorneo::where('id_arbitro_asignado', $idInstructor)
            ->whereNotNull('fecha_hora_inicio')
            ->whereDate('fecha_hora_inicio', '>=', $hoy)
            ->whereBetween(DB::raw('DATE(fecha_hora_inicio)'), [$desde, $hasta])
            ->whereNotIn('estatus_encuentro', ['CANCELADO', 'FINALIZADO', 'BYE'])
            ->whereHas('torneo', fn($q) => $q->where('estatus_torneo', '!=', 'CANCELADO'))
            ->with([
                'torneo:id_torneo,nombre_torneo',
                'espacioFisico:id_espacio,nombre_espacio',
            ])
            ->orderBy('fecha_hora_inicio')
            ->get()
            ->map(fn($e) => [
                'id_encuentro' => $e->id_encuentro,
                'tipo'         => 'TORNEO',
                'titulo'       => $e->torneo?->nombre_torneo ?? 'Torneo',
                'fecha'        => Carbon::parse($e->fecha_hora_inicio)->toDateString(),
                'hora_inicio'  => Carbon::parse($e->fecha_hora_inicio)->format('H:i'),
                'hora_fin'     => $e->fecha_hora_fin ? Carbon::parse($e->fecha_hora_fin)->format('H:i') : null,
                'espacio'      => $e->espacioFisico?->nombre_espacio,
                'estatus'      => $e->estatus_encuentro,
                'fase'         => $e->fase_bracket,
                'contadores'   => null,
                'listas'       => null,
            ])
            ->toArray();
    }
}
