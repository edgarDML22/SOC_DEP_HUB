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
        $disciplinas = Disciplina::with(['categorias'])->get();
        $icons = $this->getDisciplinesIcons();
        foreach ($disciplinas as $d) {
            $d->icono = $icons[$d->id_disciplina] ?? null;
        }
        return response()->json([
            'success' => true,
            'data' => $disciplinas
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_disciplina' => 'required|string',
            'categorias_ids' => 'required|array',
            'categorias_ids.*' => 'exists:categorias,id_categoria',
            'estatus' => 'nullable|string',
            'icono' => 'nullable|string'
        ]);

        $disciplina = Disciplina::create([
            'nombre_disciplina' => $data['nombre_disciplina'],
            'estatus' => $data['estatus'] ?? 'ACTIVO',
        ]);

        if (isset($data['categorias_ids'])) {
            $disciplina->categorias()->sync($data['categorias_ids']);
        }

        if (!empty($data['icono'])) {
            $this->saveDisciplineIcon($disciplina->id_disciplina, $data['icono']);
        }

        $disciplina = Disciplina::with(['categorias'])->find($disciplina->id_disciplina);
        $disciplina->icono = $data['icono'] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Disciplina creada correctamente',
            'data' => $disciplina
        ]);
    }

    public function show($id): JsonResponse
    {
        $disciplina = Disciplina::with(['instructores', 'categorias'])->find($id);
        if (!$disciplina) {
            return response()->json(['success' => false, 'message' => 'Disciplina no encontrada'], 404);
        }
        $icons = $this->getDisciplinesIcons();
        $disciplina->icono = $icons[$disciplina->id_disciplina] ?? null;
        return response()->json(['success' => true, 'data' => $disciplina]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $disciplina = Disciplina::find($id);

        if (!$disciplina) {
            return response()->json(['message' => 'Disciplina no encontrada'], 404);
        }

        $data = $request->validate([
            'nombre_disciplina' => 'sometimes|string',
            'categorias_ids'    => 'sometimes|array',
            'categorias_ids.*'  => 'exists:categorias,id_categoria',
            'nuevo_estatus'     => 'sometimes|in:ACTIVO,PAUSA,CANCELADO',
            'icono'             => 'sometimes|nullable|string'
        ]);

        // 1. Actualizar Nombre
        if (isset($data['nombre_disciplina'])) {
            $disciplina->nombre_disciplina = $data['nombre_disciplina'];
        }

        // 2. Actualizar Categorías
        if (isset($data['categorias_ids'])) {
            $disciplina->categorias()->sync($data['categorias_ids']);
        }

        // 3. Actualizar Estatus (con validación de conflictos)
        if (isset($data['nuevo_estatus']) && $data['nuevo_estatus'] !== $disciplina->estatus) {
            $nuevo = $data['nuevo_estatus'];
            if ($nuevo !== 'ACTIVO') {
                $conflictos = $this->getActiveDependencies($id);
                $hayConflictos = $conflictos['reservaciones_activas'] > 0 ||
                                 $conflictos['sesiones_activas'] > 0 ||
                                 $conflictos['actividades_programadas'] > 0 ||
                                 count($conflictos['torneos_activos']) > 0;

                if ($hayConflictos) {
                    return response()->json([
                        'message' => 'No se puede cambiar el estatus debido a conflictos activos.',
                        'conflictos' => $conflictos
                    ], 422);
                }
            }
            $disciplina->estatus = $nuevo;
        }

        if (array_key_exists('icono', $data)) {
            $this->saveDisciplineIcon($id, $data['icono']);
        }

        $disciplina->save();

        $disciplina = Disciplina::with(['categorias', 'instructores'])->find($id);
        $icons = $this->getDisciplinesIcons();
        $disciplina->icono = $icons[$id] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Disciplina actualizada correctamente',
            'data'    => $disciplina
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
        $actividades = ActividadPlantilla::where('id_disciplina', $id_disciplina)
            ->whereHas('plantilla', function ($query) {
                $query->where('estatus_plantilla', true);
            })
            ->count();

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

    private function getDisciplinesIcons(): array
    {
        $path = storage_path('app/disciplines_icons.json');
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?: [];
        }
        return [];
    }

    private function saveDisciplineIcon($id_disciplina, $icon): void
    {
        $path = storage_path('app/disciplines_icons.json');
        $icons = $this->getDisciplinesIcons();
        if (empty($icon)) {
            unset($icons[$id_disciplina]);
        } else {
            $icons[$id_disciplina] = $icon;
        }
        
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($icons, JSON_PRETTY_PRINT));
    }
}
