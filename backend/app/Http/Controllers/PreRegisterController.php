<?php

namespace App\Http\Controllers;

use App\Models\PreRegistroTorneo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Torneo;
use Illuminate\Support\Str;
class PreRegisterController extends Controller
{
    public function index(Request $request, $id)
    {
        $torneo = Torneo::findOrFail($id);

        $preRegistros = PreRegistroTorneo::where('id_torneo', $id)
            ->when($request->estatus, function ($query) use ($request) {
                $query->where('estatus', $request->estatus);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'torneo' => $torneo,
            'data' => $preRegistros
        ]);
    }

    public function store(Request $request, $id)
    {
        // Validar que el torneo existe
        $torneo = Torneo::findOrFail($id);

        // Validar campos
        $request->validate([
            'tipo' => 'required|in:INDIVIDUAL,EQUIPO',

            'nombre_completo' => 'required|string|max:255',
            'correo' => 'required|email|max:255',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|in:M,F,X',

            'ranking_declarado' => 'required|integer|min:0|max:500',

            'ine_pdf' => 'required|mimes:pdf|max:2048',
            'curp_pdf' => 'required|mimes:pdf|max:2048',
            'carta_responsiva_pdf' => 'required|mimes:pdf|max:2048',
        ]);

        // Validar que el torneo esté en inscripción
        if ($torneo->estatus_torneo !== 'EN_INSCRIPCION') {
            return response()->json([
                'success' => false,
                'message' => 'El torneo no está recibiendo inscripciones.'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | RUTAS DE ALMACENAMIENTO LOCAL
        |--------------------------------------------------------------------------
        |
        | Los PDFs se guardan localmente en:
        |
        | C:\torneos-storage\
        |
        | Subcarpetas:
        |
        | C:\torneos-storage\ines
        | C:\torneos-storage\curps
        | C:\torneos-storage\responsivas
        |
        | IMPORTANTE:
        | Todos los integrantes del equipo deben crear manualmente
        | la carpeta:
        |
        | C:\torneos-storage
        |
        | y configurar esa ruta en config/filesystems.php
        |
        */


        // Guardar PDFs localmente
        $inePath = $request->file('ine_pdf')->store(
            'ines',
            'torneos_storage'
        );

        $curpPath = $request->file('curp_pdf')->store(
            'curps',
            'torneos_storage'
        );

        $responsivaPath = $request->file('carta_responsiva_pdf')->store(
            'responsivas',
            'torneos_storage'
        );

        // Crear preregistro
        $preRegistro = PreRegistroTorneo::create([
            'id_torneo' => $id,

            'tipo' => $request->tipo,

            'estatus' => 'PENDIENTE',

            'datos_participante' => [
                'nombre_completo' => $request->nombre_completo,
                'correo' => $request->correo,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'genero' => $request->genero,
                'ranking_declarado' => $request->ranking_declarado,
            ],

            'urls_documentos' => [
                'ine_pdf' => $inePath,
                'curp_pdf' => $curpPath,
                'responsiva_pdf' => $responsivaPath,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pre-registro creado exitosamente',
            'preRegistro' => $preRegistro
        ], 201);
    }

    public function aprobar(Request $request, $id, $registroId)
    {
        $registro = PreRegistroTorneo::findOrFail($registroId);

        // Validar idempotencia
        if ($registro->estatus !== 'PENDIENTE') {
            return response()->json([
                'success' => false,
                'message' => 'El preregistro ya fue procesado.'
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pendiente implementación Task 14.2'
        ], 202);
    }

    public function rechazar(Request $request, $id, $registroId)
    {
        $request->validate([
            'motivo' => 'required|string|min:10'
        ]);
        $registro = PreRegistroTorneo::findOrFail($registroId);
        // Validar idempotencia
        if ($registro->estatus !== 'PENDIENTE') {
            return response()->json([
                'success' => false,
                'message' => 'El preregistro ya fue procesado.'
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pendiente implementación Task 14.2'
        ], 202);
    }
}