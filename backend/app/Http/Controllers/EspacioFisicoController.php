<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsultarDisponibilidadRequest;
use App\Models\ActividadPlantilla;
use App\Models\EncuentrosTorneo;
use App\Models\EspacioFisico;
use App\Models\Reservacion;
use App\Models\SesionActiva;
use App\Models\torneos;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => EspacioFisico::with('disciplinas')->get()
        ]);
    }

    public function store(\Illuminate\Http\Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_espacio' => 'required|string',
            'capacidad_maxima' => 'required|integer',
            'es_reserva_on_demand' => 'required|boolean',
            'es_clase_programada' => 'required|boolean',
            'es_uso_libre' => 'required|boolean',
            'estatus' => 'required|string',
            'descripcion' => 'nullable|string',
            'disciplinas' => 'array',
        ]);

        if ($data['es_uso_libre']) {
            $data['es_reserva_on_demand'] = false;
            $data['es_clase_programada'] = false;
        }

        if (!$data['es_uso_libre'] && !$data['es_reserva_on_demand'] && !$data['es_clase_programada']) {
            return response()->json(['success' => false, 'message' => 'Al menos un tipo de uso debe estar activo.'], 422);
        }

        $espacio = EspacioFisico::create($data);

        if (isset($data['disciplinas'])) {
            $espacio->disciplinas()->sync($data['disciplinas']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Espacio creado correctamente',
            'data' => $espacio->load('disciplinas')
        ]);
    }

    public function show($id): JsonResponse
    {
        $espacio = EspacioFisico::with('disciplinas')->find($id);
        if (!$espacio) {
            return response()->json(['success' => false, 'message' => 'Espacio no encontrado'], 404);
        }
        return response()->json(['success' => true, 'data' => $espacio]);
    }

    public function update(\Illuminate\Http\Request $request, $id): JsonResponse
    {
        $espacio = EspacioFisico::find($id);
        if (!$espacio) {
            return response()->json([
                'success' => false,
                'message' => 'Espacio no encontrado'
            ], 404);
        }

        $data = $request->validate([
            'nombre_espacio' => 'sometimes|string',
            'capacidad_maxima' => 'sometimes|integer',
            'es_reserva_on_demand' => 'sometimes|boolean',
            'es_clase_programada' => 'sometimes|boolean',
            'es_uso_libre' => 'sometimes|boolean',
            'descripcion' => 'nullable|string',
            'disciplinas' => 'array',
            'nuevo_estatus' => 'sometimes|in:ACTIVO,MANTENIMIENTO,DESHABILITADO',
        ]);

        if (isset($data['nuevo_estatus'])) {
            $data['estatus'] = $data['nuevo_estatus'];
            unset($data['nuevo_estatus']);
        }

        // Si es uso libre, desactiva los otros
        if (isset($data['es_uso_libre']) && $data['es_uso_libre']) {
            $data['es_reserva_on_demand'] = false;
            $data['es_clase_programada'] = false;
        }

        // Validar que al menos uno esté activo
        $reserva = $data['es_reserva_on_demand'] ?? $espacio->es_reserva_on_demand;
        $clase = $data['es_clase_programada'] ?? $espacio->es_clase_programada;
        $uso = $data['es_uso_libre'] ?? $espacio->es_uso_libre;

        if (!$reserva && !$clase && !$uso) {
            return response()->json([
                'success' => false,
                'message' => 'Al menos un tipo de uso debe estar activo.'
            ], 422);
        }

        // Validación de estatus
        if (isset($data['estatus']) && $data['estatus'] !== $espacio->estatus) {

            if (in_array($data['estatus'], ['DESHABILITADO', 'MANTENIMIENTO'])) {

                $conflictos = $this->getActiveDependencies($id);

                if (array_sum($conflictos) > 0) {
                    return response()->json([
                        'message' => 'No se puede cambiar el estatus',
                        'conflictos' => $conflictos
                    ], 422);
                }
            }
        }

        $espacio->update($data);

        if (isset($data['disciplinas'])) {
            $espacio->disciplinas()->sync($data['disciplinas']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Espacio actualizado correctamente',
            'data' => $espacio->load('disciplinas')
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $espacio = EspacioFisico::find($id);
        if (!$espacio) {
            return response()->json(['success' => false, 'message' => 'Espacio no encontrado'], 404);
        }

        $conflictos = $this->getActiveDependencies($id);

        if (array_sum($conflictos) > 0) {
            return response()->json([
                'message' => 'No se puede cambiar el estatus',
                'conflictos' => $conflictos
            ], 422);
        }

        $espacio->update(['estatus' => 'DESHABILITADO']);
        return response()->json(['success' => true, 'message' => 'Espacio deshabilitado correctamente']);
    }

    /**
     * Verifica si el espacio tiene dependencias activas o futuras.
     */
    private function getActiveDependencies($id_espacio): array
    {
        $today = now()->toDateString();

        //reservaciones
        $reservaciones = Reservacion::where('id_espacio', $id_espacio)
            ->where('fecha_reserva', '>=', $today)
            ->where(function ($q) {
                $q->where('estatus_operativo', 'ACTIVA')
                    ->orWhere(function ($sub) {
                        $sub->where('estatus_operativo', 'PENDIENTE')
                            ->where('fecha_expiracion', '>', now());
                    });
            })
            ->count();

        //torneos activos
        $torneos = EncuentrosTorneo::where('id_espacio', $id_espacio)->count();

        //sesiones activas
        $sesiones = SesionActiva::whereHas('actividadPlantilla', function ($query) use ($id_espacio) {
            $query->where('id_espacio', $id_espacio);
        })
            ->where('estatus_sesion', 'EN_CURSO')
            ->count();

        //actividades programadas
        $actividades = ActividadPlantilla::where('id_espacio', $id_espacio)->count();

        return [
            'reservaciones_activas' => $reservaciones,
            'sesiones_activas' => $sesiones,
            'actividades_programadas' => $actividades,
            'torneos_programados' => $torneos
        ];
    }

    private function getDisponibilidadOnDemand($fecha): array
    {
        // 1. LA CONSULTA MAESTRA (¡Ultra ligera!)
        $query = EspacioFisico::select('id_espacio', 'nombre_espacio', 'capacidad_maxima', 'estatus')
            ->where('es_reserva_on_demand', true)
            ->with(['disciplinas:id_disciplina,nombre_disciplina']);

        $espacios = $query->get();


        // 2. EL MAPEO
        $resultado = $espacios->map(function ($espacio) {
            $estatus = 'Disponible';

            if ($espacio->estatus === 'MANTENIMIENTO') {
                $estatus = 'Bloqueado por Mantenimiento';
            }

            // Mapeamos las disciplinas limpio (El array de objetos que tu front ya espera)
            $disciplinas = $espacio->disciplinas->isEmpty()
                ? [['id_disciplina' => null, 'nombre_disciplina' => 'N/A']]
                : $espacio->disciplinas->toArray();

            return [
                'id_espacio' => $espacio->id_espacio,
                'nombre_espacio' => $espacio->nombre_espacio,
                'disciplinas' => $disciplinas,
                'estatus' => $estatus,
                'capacidad_maxima' => $espacio->capacidad_maxima
            ];
        });

        return $resultado->toArray();
    }


    private function getActividadProgramada($fecha, $categoria_disciplina = null): array
    {
        // 1. Iniciamos la consulta en sesiones_activas
        $query = SesionActiva::where('fecha_sesion', $fecha)
            ->whereHas('actividadPlantilla.espacioFisico', function ($q) {
                $q->where('es_clase_programada', true);
            })
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
                'espacio_id' => $espacio->id_espacio,
                'nombre' => $espacio->nombre_espacio . ' - ' . $disciplina->nombre_disciplina,
                'categoria' => $disciplina->categoria_disciplina,
                'hora_inicio' => $plantilla->hora_inicio,
                'hora_fin' => $plantilla->hora_fin,
                'estatus' => $estatus,
                'cupo_restante' => $cupoRestante
            ];
        });

        return $resultado->toArray();
    }
}
