<?php

namespace App\Http\Controllers;

use App\Models\PreRegistroTorneo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Torneo;
use Illuminate\Support\Str;

use App\Actions\Torneo\AprobarPreRegistroAction;
use App\Actions\Torneo\RechazarPreRegistroAction;
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
        // Validar torneo
        $torneo = Torneo::findOrFail($id);

        // Validar torneo abierto
        if ($torneo->estatus_torneo !== 'EN_INSCRIPCION') {
            return response()->json([
                'success' => false,
                'message' => 'El torneo no está recibiendo inscripciones.'
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        if ($request->tipo == 'INDIVIDUAL') {

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

            // Validar duplicado mismo torneo
            $registroExistente = PreRegistroTorneo::where('id_torneo', $id)
                ->get()
                ->first(function ($registro) use ($request) {

                    return isset($registro->datos_participante['correo']) &&
                        strtolower($registro->datos_participante['correo']) === strtolower($request->correo);
                });

            if ($registroExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un preregistro con ese correo para este torneo.'
                ], 409);
            }

            // IDs
            $preRegistroId = substr(Str::uuid(), 0, 8);

            // Normalizar
            $nombreJugador = Str::slug($request->nombre_completo);
            $nombreTorneo = Str::slug($torneo->nombre_torneo);

            // INE
            $ineName = 'ine_' . $preRegistroId . '_' . $nombreJugador . '_' . $nombreTorneo . '.pdf';

            $inePath = $request->file('ine_pdf')->storeAs(
                'ines',
                $ineName,
                'torneos_storage'
            );

            // CURP
            $curpName = 'curp_' . $preRegistroId . '_' . $nombreJugador . '_' . $nombreTorneo . '.pdf';

            $curpPath = $request->file('curp_pdf')->storeAs(
                'curps',
                $curpName,
                'torneos_storage'
            );

            // RESPONSIVA
            $responsivaName = 'responsiva_' . $preRegistroId . '_' . $nombreJugador . '_' . $nombreTorneo . '.pdf';

            $responsivaPath = $request->file('carta_responsiva_pdf')->storeAs(
                'responsivas',
                $responsivaName,
                'torneos_storage'
            );

            // Crear preregistro
            $preRegistro = PreRegistroTorneo::create([

                'id_torneo' => $id,

                'tipo' => 'INDIVIDUAL',

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
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pre-registro individual creado.',
                'preRegistro' => $preRegistro
            ], 201);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN EQUIPO
        |--------------------------------------------------------------------------
        */

        if ($request->tipo == 'EQUIPO') {

            $request->validate([

                'tipo' => 'required|in:INDIVIDUAL,EQUIPO',

                'nombre_equipo' => 'required|string|max:150',

                'integrantes' => 'required|array|min:1',

                'integrantes.*.nombre_completo' => 'required|string|max:255',
                'integrantes.*.correo' => 'required|email|max:255',
                'integrantes.*.fecha_nacimiento' => 'required|date',
                'integrantes.*.genero' => 'required|in:M,F,X',
                'integrantes.*.ranking_declarado' => 'required|integer|min:0|max:500',

                'integrantes.*.ine_pdf' => 'required|file|mimes:pdf|max:2048',
                'integrantes.*.curp_pdf' => 'required|file|mimes:pdf|max:2048',
                'integrantes.*.carta_responsiva_pdf' => 'required|file|mimes:pdf|max:2048',
            ]);

            $datosIntegrantes = [];
            $documentosIntegrantes = [];

            foreach ($request->integrantes as $integrante) {

                // Validar correo duplicado mismo torneo
                $registroExistente = PreRegistroTorneo::where('id_torneo', $id)
                    ->get()
                    ->first(function ($registro) use ($integrante) {

                        if (!isset($registro->datos_participante['integrantes'])) {
                            return false;
                        }

                        foreach ($registro->datos_participante['integrantes'] as $existente) {

                            if (
                                strtolower($existente['correo']) ===
                                strtolower($integrante['correo'])
                            ) {
                                return true;
                            }
                        }

                        return false;
                    });

                if ($registroExistente) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Uno de los correos ya está registrado en este torneo.'
                    ], 409);
                }

                $preRegistroId = substr(Str::uuid(), 0, 8);

                $nombreJugador = Str::slug($integrante['nombre_completo']);
                $nombreTorneo = Str::slug($torneo->nombre_torneo);

                // INE
                $ineName = 'ine_' . $preRegistroId . '_' . $nombreJugador . '_' . $nombreTorneo . '.pdf';

                $inePath = $integrante['ine_pdf']->storeAs(
                    'ines',
                    $ineName,
                    'torneos_storage'
                );

                // CURP
                $curpName = 'curp_' . $preRegistroId . '_' . $nombreJugador . '_' . $nombreTorneo . '.pdf';

                $curpPath = $integrante['curp_pdf']->storeAs(
                    'curps',
                    $curpName,
                    'torneos_storage'
                );

                // RESPONSIVA
                $responsivaName = 'responsiva_' . $preRegistroId . '_' . $nombreJugador . '_' . $nombreTorneo . '.pdf';

                $responsivaPath = $integrante['carta_responsiva_pdf']->storeAs(
                    'responsivas',
                    $responsivaName,
                    'torneos_storage'
                );

                // Datos integrante
                $datosIntegrantes[] = [

                    'nombre_completo' => $integrante['nombre_completo'],
                    'correo' => $integrante['correo'],
                    'fecha_nacimiento' => $integrante['fecha_nacimiento'],
                    'genero' => $integrante['genero'],
                    'ranking_declarado' => $integrante['ranking_declarado'],
                ];

                // PDFs integrante
                $documentosIntegrantes[$integrante['correo']] = [

                    'ine_pdf' => $inePath,
                    'curp_pdf' => $curpPath,
                    'responsiva_pdf' => $responsivaPath,
                ];
            }

            // Crear preregistro equipo
            $preRegistro = PreRegistroTorneo::create([

                'id_torneo' => $id,

                'tipo' => 'EQUIPO',

                'estatus' => 'PENDIENTE',

                'datos_participante' => [

                    'nombre_equipo' => $request->nombre_equipo,

                    'integrantes' => $datosIntegrantes
                ],

                'urls_documentos' => $documentosIntegrantes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pre-registro de equipo creado.',
                'preRegistro' => $preRegistro
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tipo inválido.'
        ], 422);
    }

    public function aprobar($id, $registroId)
    {
        $preRegistro = PreRegistroTorneo::find($registroId);

        if (!$preRegistro) {
            return response()->json([
                'success' => false,
                'message' => 'Pre-registro no encontrado.'
            ], 404);
        }

        if ($preRegistro->id_torneo != $id) {
            return response()->json([
                'success' => false,
                'message' => 'El preregistro no pertenece al torneo.'
            ], 404);
        }

        if ($preRegistro->estatus != 'PENDIENTE') {
            return response()->json([
                'success' => false,
                'message' => 'El preregistro ya fue procesado.'
            ], 409);
        }

        AprobarPreRegistroAction::execute($preRegistro);

        return response()->json([
            'success' => true,
            'message' => 'Pre-registro enviado a aprobación.'
        ], 202);
    }
    public function rechazar(Request $request, $id, $registroId)
    {
        $request->validate([
            'motivo_rechazo' => 'required|string|max:255'
        ]);

        $preRegistro = PreRegistroTorneo::find($registroId);

        if (!$preRegistro) {
            return response()->json([
                'success' => false,
                'message' => 'Pre-registro no encontrado.'
            ], 404);
        }

        if ($preRegistro->estatus != 'PENDIENTE') {
            return response()->json([
                'success' => false,
                'message' => 'El preregistro ya fue procesado.'
            ], 409);
        }

        RechazarPreRegistroAction::execute(
            $preRegistro,
            $request->motivo_rechazo
        );

        return response()->json([
            'success' => true,
            'message' => 'Pre-registro rechazado.'
        ], 202);
    }

    public function descargarDocumento(Request $request)
    {
        $path = $request->query('path');

        if (!$path || !Storage::disk('torneos_storage')->exists($path)) {
            abort(404, 'Documento no encontrado.');
        }

        return Storage::disk('torneos_storage')->response($path);
    }
}