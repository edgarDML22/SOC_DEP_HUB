<?php

namespace App\Http\Controllers;
use App\Models\Torneo;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Disciplina;
use App\Models\CategoriaTorneo;
class TorneoController extends Controller
{
    public function index(Request $request)
    {
        $relations = ['disciplina', 'categoria'];
        if ($request->has('with_encuentros')) {
            $relations[] = 'encuentros';
            $relations[] = 'encuentros.competidor1.participante';
            $relations[] = 'encuentros.competidor1.equipo';
            $relations[] = 'encuentros.competidor1.capitanDeEquipo';
            $relations[] = 'encuentros.competidor2.participante';
            $relations[] = 'encuentros.competidor2.equipo';
            $relations[] = 'encuentros.competidor2.capitanDeEquipo';
        }

        $perPage = $request->input('per_page', 15);

        $torneos = Torneo::with($relations)
            ->when($request->estatus, fn($q, $v) => $q->where('estatus_torneo', $v))
            ->when($request->nombre_disciplina, fn($q, $v) => $q->whereHas('disciplina', fn($d) => $d->where('nombre_disciplina', $v)))
            ->when($request->nombre_categoria, fn($q, $v) => $q->whereHas('categoria', fn($c) => $c->where('nombre_categoria', $v)))
            ->when($request->tipo_acceso, fn($q, $v) => $q->where('tipo_acceso', $v))
            ->paginate($perPage);

        $torneos->getCollection()->transform(function ($t) use ($request) {
            $data = [
                'id' => $t->id_torneo,
                'id_torneo' => $t->id_torneo,
                'nombre_torneo' => $t->nombre_torneo,
                'disciplina' => $t->disciplina?->nombre_disciplina,
                'categoria' => $t->categoria?->nombre_categoria,
                'tipo_acceso' => $t->tipo_acceso,
                'estado' => $t->estatus_torneo,
                'estatus_torneo' => $t->estatus_torneo,
                'fecha_inicio' => $t->fecha_inicio,
                'fecha_fin' => $t->fecha_fin,
                'cupo_maximo' => $t->cupo_maximo,
                'cupo_minimo' => $t->cupo_minimo,
                'modalidad' => $t->modalidad,
                'genero' => $t->genero_requerido,
                'motivo_cancelacion' => $t->motivo_cancelacion,
            ];

            if ($request->has('with_encuentros')) {
                $data['_encuentros'] = $t->encuentros->map(function ($e) {
                    return [
                        'id_encuentro' => $e->id_encuentro,
                        'id_torneo' => $e->id_torneo,
                        'fase_bracket' => $e->fase_bracket || $e->fase || 'N/A',
                        'fase' => $e->fase_bracket || $e->fase || 'N/A',
                        'numero_encuentro' => $e->numero_encuentro,
                        'es_bye' => $e->es_bye,
                        'fecha_hora_inicio' => $e->fecha_hora_inicio,
                        'fecha_hora_fin' => $e->fecha_hora_fin,
                        'id_espacio' => $e->id_espacio,
                        'id_arbitro_asignado' => $e->id_arbitro_asignado,
                        'estatus_encuentro' => $e->estatus_encuentro,
                        'competidor1' => $e->competidor1,
                        'competidor2' => $e->competidor2,
                    ];
                });
            }

            return $data;
        });

        if ($torneos->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron torneos'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $torneos
        ], 200);
    }

    //SDH 268 SHOW TORNEOS CREATIONS BY DATE
    public function show(int $id)
    {
        $torneo = Torneo::with([
            'disciplina',
            'categoria',
            'encuentros' => fn($q) => $q->orderBy('fase_bracket')->orderBy('numero_encuentro'),
            'encuentros.competidor1.participante',
            'encuentros.competidor1.equipo',
            'encuentros.competidor1.capitanDeEquipo',
            'encuentros.competidor2.participante',
            'encuentros.competidor2.equipo',
            'encuentros.competidor2.capitanDeEquipo',
        ])

            ->findOrFail($id);
        $encuentrosAgrupados = $torneo->encuentros->groupBy('fase_bracket');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $torneo->id_torneo,
                'id_torneo' => $torneo->id_torneo,
                'nombre_torneo' => $torneo->nombre_torneo,
                'categoria' => $torneo->categoria?->nombre_categoria,
                'disciplina' => $torneo->disciplina?->nombre_disciplina,
                'id_disciplina' => $torneo->id_disciplina,
                'fecha_inicio' => $torneo->fecha_inicio,
                'fecha_fin' => $torneo->fecha_fin,
                'tipo_acceso' => $torneo->tipo_acceso,
                'formato_competencia' => $torneo->formato_competencia,
                'cupo_maximo' => $torneo->cupo_maximo,
                'cupo_minimo' => $torneo->cupo_minimo,
                'modalidad' => $torneo->modalidad,
                'genero' => $torneo->genero_requerido,
                'descripcion' => $torneo->descripcion,
                'motivo_cancelacion' => $torneo->motivo_cancelacion,
                'estado' => $torneo->estatus_torneo,
                'estatus_torneo' => $torneo->estatus_torneo,
                'bracket' => $encuentrosAgrupados,
            ]
        ], 200);
    }
    public function store(Request $request)
    {
        /* SDH 47 */
        try {
            $request->validate([
                'nombre_categoria' => 'required|string|exists:categorias_torneo,nombre_categoria',
                'nombre_disciplina' => 'required|string|exists:disciplinas,nombre_disciplina',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'tipo_acceso' => 'required|in:INTERNO,ABIERTO',
                'formato_competencia' => 'required|in:ELIMINACION_DIRECTA,FASE_GRUPOS',
                'nombre_torneo' => 'required|string|max:100',
                'cupo_maximo' => 'required|integer|min:1',
                'descripcion' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        }
        $categoria = CategoriaTorneo::where('nombre_categoria', $request->nombre_categoria)->first();

        if (!$categoria) {
            return response()->json([
                'error' => 'La categoria no existe'
            ], 404);
        }
        $disciplina = Disciplina::where('nombre_disciplina', $request->nombre_disciplina)->first();

        if (!$disciplina) {
            return response()->json([
                'error' => 'La disciplina no existe'
            ], 404);
        }

        $id_disciplina = $disciplina->id_disciplina;

        $existe = Torneo::where('nombre_torneo', $request->nombre_torneo)
            ->where('fecha_inicio', $request->fecha_inicio)
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un torneo con ese nombre y genero'
            ], 409);
        }


        $genero_requerido = $request->genero_requerido ?: $categoria->genero_requerido;
        if ($genero_requerido) {
            $upper = strtoupper(trim($genero_requerido));
            if ($upper === 'M' || $upper === 'VARONIL' || $upper === 'MASCULINO') {
                $genero_requerido = 'VARONIL';
            } elseif ($upper === 'F' || $upper === 'FEMENIL' || $upper === 'FEMENINO') {
                $genero_requerido = 'FEMENIL';
            } elseif ($upper === 'MIXTO') {
                $genero_requerido = 'MIXTO';
            } else {
                $genero_requerido = 'MIXTO';
            }
        } else {
            $genero_requerido = 'MIXTO';
        }

        $torneo = Torneo::create([
            'nombre_torneo' => $request->nombre_torneo,
            'id_disciplina' => $id_disciplina,
            'tipo_acceso' => $request->tipo_acceso,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estatus_torneo' => 'EN_PLANIFICACION',
            'formato_competencia' => $request->formato_competencia,
            'cupo_minimo' => $request->cupo_minimo,
            'cupo_maximo' => $request->cupo_maximo,
            'genero_requerido' => $genero_requerido,
            'descripcion' => $request->descripcion,
            'id_categoria' => $categoria->id_categoria,
            'modalidad' => $request->modalidad,
        ]);
        return response()->json([
            "success" => true,
            "message" => "Torneo registrado correctamente",
            "data" => [
                "id" => $torneo->id_torneo,
                "estado" => "EN_PLANIFICACION"
            ]
        ], 201);
    }

    /**
     * Recupera el bracket de encuentros del torneo agrupado por fase.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function bracket(int $id)
    {
        $torneo = Torneo::findOrFail($id);

        $encuentros = $torneo->encuentros()
            ->with([
                'competidor1.participante',
                'competidor1.equipo',
                'competidor1.capitanDeEquipo',
                'competidor2.participante',
                'competidor2.equipo',
                'competidor2.capitanDeEquipo'
            ])
            ->orderBy('numero_encuentro', 'asc')
            ->get();

        $encuentrosAgrupados = $encuentros->groupBy('fase_bracket');

        return response()->json([
            'success' => true,
            'data' => $encuentrosAgrupados
        ], 200);
    }

}