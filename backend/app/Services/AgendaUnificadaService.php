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
     * Estatus incluidos: ACTIVA, PENDIENTE.
     * Eager loading: espacioFisico + disciplina (evita N+1).
     * Título: "Disciplina — Espacio".
     */
    private function obtenerReservaciones(int $idSocio, string $desde, string $hasta): array
    {
        return Reservacion::where('id_socio_titular', $idSocio)
            ->whereBetween('fecha_reserva', [$desde, $hasta])
            ->whereIn('estatus_operativo', ['ACTIVA', 'PENDIENTE'])
            ->with([
                'espacioFisico:id_espacio,nombre_espacio',
                'disciplina:id_disciplina,nombre_disciplina',
            ])
            ->get()
            ->map(fn($r) => [
                'tipo'        => 'RESERVA',
                'titulo'      => trim(
                    ($r->disciplina?->nombre_disciplina ?? 'Reservación')
                    . ($r->espacioFisico ? ' — ' . $r->espacioFisico->nombre_espacio : '')
                ),
                'fecha'       => $r->fecha_reserva,
                'hora_inicio' => substr($r->hora_inicio, 0, 5),
                'hora_fin'    => substr($r->hora_fin, 0, 5),
                'espacio'     => $r->espacioFisico?->nombre_espacio,
                'instructor'  => null,
                'estatus'     => $r->estatus_operativo,
            ])
            ->toArray();
    }

    /**
     * Consulta las inscripciones a clases del socio en el rango dado.
     * Estatus incluidos: CONFIRMADA, LISTA, ESPERA.
     * Usa withoutGlobalScopes() en sesion para evitar que FuturasActivasScope
     * restrinja el rango de fechas solicitado por el usuario.
     * Usa withTrashed() en actividadPlantilla para no perder datos de sesiones
     * cuya plantilla fue eliminada lógicamente (SoftDeletes).
     * Instructor: el de la plantilla base (no el sustituto de sesiones_activas).
     */
    private function obtenerClases(int $idSocio, string $desde, string $hasta): array
    {
        return InscripcionClase::where('id_usuario', $idSocio)
            ->whereIn('estatus_inscripcion', ['CONFIRMADA', 'LISTA', 'ESPERA'])
            ->whereHas('sesion', fn($q) =>
                $q->withoutGlobalScopes()
                  ->whereBetween('fecha_sesion', [$desde, $hasta])
            )
            ->with([
                // withoutGlobalScopes() omite FuturasActivasScope + select mínimo.
                // FK id_actividad_plantilla es obligatoria para resolver la relación hija.
                'sesion'                    => fn($q) => $q->withoutGlobalScopes()
                                                           ->select([
                                                               'id_sesion',
                                                               'id_actividad_plantilla',
                                                               'fecha_sesion',
                                                           ]),
                // withTrashed() para no perder datos de plantillas con SoftDelete.
                // FK id_disciplina, id_espacio, id_instructor son obligatorias para las relaciones hijas.
                'sesion.actividadPlantilla' => fn($q) => $q->withTrashed()
                                                           ->select([
                                                               'id_actividad_plantilla',
                                                               'id_disciplina',
                                                               'id_espacio',
                                                               'id_instructor',
                                                               'hora_inicio',
                                                               'hora_fin',
                                                           ]),
                'sesion.actividadPlantilla.disciplina:id_disciplina,nombre_disciplina',
                'sesion.actividadPlantilla.espacioFisico:id_espacio,nombre_espacio',
                'sesion.actividadPlantilla.instructor:id_instructor,nombre_completo',
            ])
            ->get()
            ->map(fn($i) => [
                'tipo'        => 'CLASE',
                'titulo'      => $i->sesion?->actividadPlantilla?->disciplina?->nombre_disciplina ?? 'Clase',
                'fecha'       => $i->sesion?->fecha_sesion,
                'hora_inicio' => substr($i->sesion?->actividadPlantilla?->hora_inicio ?? '', 0, 5),
                'hora_fin'    => substr($i->sesion?->actividadPlantilla?->hora_fin ?? '', 0, 5),
                'espacio'     => $i->sesion?->actividadPlantilla?->espacioFisico?->nombre_espacio,
                'instructor'  => $i->sesion?->actividadPlantilla?->instructor?->nombre_completo,
                'estatus'     => $i->estatus_inscripcion,
            ])
            ->toArray();
    }
}
