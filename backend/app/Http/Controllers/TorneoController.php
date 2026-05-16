<?php

namespace App\Http\Controllers;
use App\Models\Torneo;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Disciplina;
use App\Models\CategoriaTorneo;
class TorneoController extends Controller
{
    public function index()
    {
        $torneos = Torneo::with(['disciplina', 'categoria'])->get();

        $data = $torneos->map(function ($torneo) {
            return [
                'nombre_torneo' => $torneo->nombre_torneo,
                'categoria' => $torneo->categoria?->nombre_categoria,
                'disciplina' => $torneo->disciplina?->nombre_disciplina,
                'fecha_inicio' => $torneo->fecha_inicio,
                'estado' => $torneo->estatus_torneo,
            ];
        });

        return response()->json([
            "success" => true,
            "data" => $data
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


        $torneo = Torneo::create([
            'nombre_torneo' => $request->nombre_torneo,
            'id_disciplina' => $id_disciplina,
            'tipo_acceso' => $request->tipo_acceso,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estatus_torneo' => 'EN_PLANIFICACION',
            'formato_competencia' => $request->formato_competencia,
            'cupo_maximo' => $request->cupo_maximo,
            'descripcion' => $request->descripcion,
            'id_categoria' => $categoria->id_categoria,
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

}