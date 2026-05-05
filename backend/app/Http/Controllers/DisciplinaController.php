<?php

namespace App\Http\Controllers;

use App\Models\Disciplina;
use App\Models\SesionActiva;
use App\Models\torneos;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Reservacion;
use App\Models\ActividadPlantilla;
use App\Models\EncuentrosTorneo;
class DisciplinaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Disciplina::with(['categorias'])->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_disciplina' => 'required|string',
            'categorias_ids' => 'required|array',
            'categorias_ids.*' => 'exists:categorias,id_categoria',
            'descripcion' => 'nullable|string',
            'estatus' => 'nullable|string'
        ]);

        $disciplina = Disciplina::create($data);

        if (isset($data['categorias_ids'])) {
            $disciplina->categorias()->sync($data['categorias_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Disciplina creada correctamente',
            'data' => $disciplina->load('categorias')
        ]);
    }

    public function show($id): JsonResponse
    {
        $disciplina = Disciplina::with(['instructores', 'categorias'])->find($id);
        if (!$disciplina) {
            return response()->json(['success' => false, 'message' => 'Disciplina no encontrada'], 404);
        }
        return response()->json(['success' => true, 'data' => $disciplina]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $disciplina = Disciplina::find($id);

        if (!$disciplina) {
            return response()->json([
                'message' => 'Disciplina no encontrada'
            ], 404);
        }

        $data = $request->validate([
            'nuevo_estatus' => 'required|in:ACTIVO,PAUSA,CANCELADO'
        ]);

        $nuevo = $data['nuevo_estatus'];

        if ($nuevo !== $disciplina->estatus) {

            if ($nuevo !== 'ACTIVO') {

                $conflictos = $this->getActiveDependencies($id);

                $hayConflictos =
                    $conflictos['reservaciones_activas'] > 0 ||
                    $conflictos['sesiones_activas'] > 0 ||
                    $conflictos['actividades_programadas'] > 0 ||
                    count($conflictos['torneos_activos']) > 0;

                if ($hayConflictos) {
                    return response()->json([
                        'message' => 'No se puede desactivar',
                        'conflictos' => $conflictos
                    ], 422);
                }
            }
        }

        $disciplina->estatus = $nuevo;
        $disciplina->save();

        return response()->json([
            'message' => 'Estatus actualizado correctamente',
            'data' => $disciplina
        ]);
    }



    private function getActiveDependencies($id_disciplina): array
    {
        //reservaciones
        $reservaciones = Reservacion::where('id_disciplina', $id_disciplina)
            ->where(function ($q) {
                $q->where('estatus_operativo', 'ACTIVA')
                    ->orWhere(function ($sub) {
                        $sub->where('estatus_operativo', 'PENDIENTE');
                    });
            })
            ->count();

        //torneos activos       
        $torneos = torneos::where('id_disciplina', $id_disciplina)
            ->whereIn('estatus_torneo', [
                'EN_INSCRIPCION',
                'EN_PLANIFICACION',
                'PROGRAMADO',
                'EN_CURSO'
            ])
            ->pluck('nombre_torneo');

        //sesiones activas
        $sesiones = SesionActiva::whereHas('actividadPlantilla', function ($query) use ($id_disciplina) {
            $query->where('id_disciplina', $id_disciplina);
        })
            ->where('estatus_sesion', 'EN_CURSO')
            ->count();

        //actividades programadas
        $actividades = ActividadPlantilla::where('id_disciplina', $id_disciplina)->count();

        return [
            'reservaciones_activas' => $reservaciones,
            'sesiones_activas' => $sesiones,
            'actividades_programadas' => $actividades,
            'torneos_activos' => $torneos
        ];
    }
    public function destroy($id): JsonResponse
    {
        $disciplina = Disciplina::find($id);
        if (!$disciplina) {
            return response()->json(['success' => false, 'message' => 'Disciplina no encontrada'], 404);
        }

        if ($this->getActiveDependencies($id)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar la disciplina porque tiene sesiones o torneos activos vinculados.'
            ], 422);
        }

        $disciplina->update(['estatus' => 'CANCELADO']);

        return response()->json([
            'success' => true,
            'message' => 'Disciplina cancelada correctamente'
        ]);
    }
}
