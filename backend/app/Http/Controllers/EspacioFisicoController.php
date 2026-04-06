<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsultarDisponibilidadRequest;
use App\Models\EspacioFisico;
use App\Models\SesionActiva;
use Illuminate\Http\JsonResponse;

class EspacioFisicoController extends Controller
{
    public function getAvailability(ConsultarDisponibilidadRequest $request): JsonResponse
    {
        $filtros = $request->validated();

        $fecha = $filtros['date'] ?? now()->toDateString();

        $categoria_disciplina = $filtros['category'] ?? null;
        $tipo_espacio = $filtros['espacio_type'] ?? null;

        $resultado = match ($tipo_espacio) {
            'RESERVA_ON_DEMAND' => $this->getDisponibilidadOnDemand($fecha),
            'CLASE_PROGRAMADA' => $this->getActividadProgramada($fecha, $categoria_disciplina),
        };

        return response()->json([
            'success' => true,
            'data' => $resultado
        ]);
    }

    private function getDisponibilidadOnDemand($fecha): array
    {
        // 1. LA CONSULTA MAESTRA CON RESTRICCIÓN
        $query = EspacioFisico::where('tipo_espacio', 'RESERVA_ON_DEMAND')
            ->with(['reservaciones' => function ($queryRelacion) use ($fecha) {
                $queryRelacion->where('fecha_reserva', $fecha);
            }]);

        $espacios = $query->get();

        // 2. EL MAPEO
        $resultado = $espacios->map(function ($espacio) {
            $reservasDeHoy = $espacio->reservaciones->count();
            $cupoMaximo = $espacio->capacidad_maxima;

            $estatus = ($reservasDeHoy >= $cupoMaximo) ? 'Lleno/No Disponible' : 'Disponible';
            $cupoRestante = max(0, $cupoMaximo - $reservasDeHoy);

            if ($espacio->estatus === 'MANTENIMIENTO') {
                $estatus = 'Bloqueado por Mantenimiento';
                $cupoRestante = 0;
            }

            return [
                'espacio_id' => $espacio->id_espacio,
                'nombre'     => $espacio->nombre_espacio,
                'categoria'  => 'N/A',
                'horario'    => 'Sujeto a disponibilidad',
                'estatus'     => $estatus,
                'cupo_restante' => $cupoRestante
            ];
        });

        return $resultado->toArray();
    }


    private function getActividadProgramada($fecha, $categoria_disciplina = null): array
    {
        // 1. Iniciamos la consulta en sesiones_activas
        $query = SesionActiva::where('fecha_sesion', $fecha)
            ->with([
                'actividadPlantilla.espacioFisico',
                'actividadPlantilla.disciplina'
            ])
            ->withCount('inscripcionesClase');

        // 2. Filtro por categoría (whereHas)
        if ($categoria_disciplina) {
            $query->whereHas('actividadPlantilla.disciplina', function ($q) use ($categoria_disciplina) {
                $q->where('categoria_disciplina', $categoria_disciplina);
            });
        }

        // Ejecutamos la consulta
        $sesiones = $query->get();

        // 3. Mapeo de datos al formato JSON que pide tu Frontend
        $resultado = $sesiones->map(function ($sesion) {

            // Extraemos las relaciones para no escribir tanto
            $plantilla = $sesion->actividadPlantilla;
            $espacio = $plantilla->espacioFisico;
            $disciplina = $plantilla->disciplina;

            // Calculamos cupos
            $cupoOcupado = $sesion->inscripciones_clase_count;
            $cupoMaximo = $plantilla->cupo_maximo;

            $cupoRestante = max(0, $cupoMaximo - $cupoOcupado);
            $estatus = ($cupoOcupado >= $cupoMaximo) ? 'Lleno/No Disponible' : 'Disponible';

            // Validamos si el espacio está en mantenimiento
            if ($espacio->estatus === 'MANTENIMIENTO') {
                $estatus = 'Bloqueado por Mantenimiento';
                $cupoRestante = 0;
            }

            return [
                'espacio_id'    => $espacio->id_espacio,
                'nombre'        => $espacio->nombre_espacio . ' - ' . $disciplina->nombre_disciplina,
                'categoria'     => $disciplina->categoria_disciplina,
                'hora_inicio'   => $plantilla->hora_inicio,
                'hora_fin'       => $plantilla->hora_fin,
                'estatus'        => $estatus,
                'cupo_restante' => $cupoRestante
            ];
        });

        return $resultado->toArray();
    }
}
