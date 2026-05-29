<?php

namespace App\Http\Controllers;

use App\Models\MiembrosFamiliares;
use Illuminate\Http\Request;
use App\Models\SocioTitular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MiembrosFamiliaresController extends Controller
{
    // MOSTRAR TODOS
    // --- MÉTODO SHOW ---
    public function show(Request $request)
    {
        $id_socio = $request->user()->user_id;

        $miembros = MiembrosFamiliares::with(['codigoQrActivo:id_codigo,usuario_id,tipo_usuario,codigo,estatus'])
            ->select(['id_miembro', 'socio_id', 'nombre_completo', 'parentesco', 'fecha_nacimiento', 'genero', 'correo', 'contador_no_shows'])
            ->where('socio_id', $id_socio)
            ->get();

        $miembros->each(function ($miembro) {
            $miembro->codigo_qr = $miembro->codigoQrActivo?->codigo ?? 'QR_NO_ENCONTRADO';
            unset($miembro->codigoQrActivo);
        });

        return response()->json(['success' => true, 'data' => $miembros], 200);
    }

    // --- MÉTODO DESTROY ---
    public function destroy(Request $request, $id)
    {
        $id_socio = $request->user()->user_id;
        $miembro = MiembrosFamiliares::where('id_miembro', $id)->where('socio_id', $id_socio)->first();

        if (!$miembro) return response()->json(['success' => false, 'message' => 'No encontrado'], 404);

        try {
            DB::transaction(function () use ($miembro) {
                // Actualizamos el estatus del QR usando la relación polimórfica
                if ($miembro->codigoQrActivo) {
                    $miembro->codigoQrActivo->update(['estatus_codigo_qr' => 'INACTIVO']);
                }
                $miembro->delete();
            });
            return response()->json(['success' => true, 'message' => 'Eliminado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al procesar'], 500);
        }
    }



    // CREAR 
    public function store(Request $request)
    {
        $id_socio = $request->user()->user_id;

        // VALIDACION DATOS FRONTEND
        $request->validate([
            'nombre_completo'    => 'required|string|max:100',
            'parentesco'         => 'required|in:CONYUGE,HIJO/A,OTRO',
            'fecha_nacimiento'   => 'required|date',
            'genero'             => 'required|in:M,F,O,OTRO',
            'correo' => 'nullable|email|max:255'
        ]);

        // COMPROBACION CORREOS DUPLICADOS
        if (!empty($request->correo)) {
            $existeCorreo = MiembrosFamiliares::where('socio_id', $id_socio)
                ->where('correo', $request->correo)
                ->exists();

            if ($existeCorreo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya has registrado a un miembro familiar con este correo electrónico.'
                ], 422);
            }
        }

        //COMPROBACION MAXIMO 6 MIEMBROS FAMILIARES
        $contador = MiembrosFamiliares::where('socio_id', $id_socio)->count();

        if ($contador >= 6) {
            return response()->json([
                'success' => false,
                'message' => 'Se ha alcanzado el límite máximo permitido de 6 miembros familiares asociados a su cuenta. Para registrar a un nuevo integrante, es necesario gestionar la baja de uno de los registros actuales.'
            ], 422);
        }




        // GENERACION CODIGO QR UNICO Y GLOBAL
        do {
            // Generamos un identificador MF + 6 caracteres aleatorios únicos (Ej: MF6A8B10)
            $codigoString = 'MF' . strtoupper(substr(str_replace('-', '', Str::uuid()), 0, 6));

            $existe = DB::table('codigos_qr')
                ->where('codigo', $codigoString)
                ->exists();
        } while ($existe);


        try {
            $result = DB::transaction(function () use ($id_socio, $request, $codigoString) {

                // CREACION MIEMBRO FAMILIAR
                $miembro = MiembrosFamiliares::create([
                    'socio_id'           => $id_socio,
                    'nombre_completo'    => $request->nombre_completo,
                    'parentesco'         => $request->parentesco,
                    'fecha_nacimiento'   => $request->fecha_nacimiento,
                    'genero'             => $request->genero,
                    'correo' => $request->correo,
                ]);

                //
                DB::table('codigos_qr')->insert([
                    'codigo'           => $codigoString,
                    'usuario_id'       => $miembro->id_miembro,
                    'tipo_usuario'     => 'FAMILIAR',
                    'estatus'          => 'ACTIVO',
                    'fecha_activacion' => now(),
                    'created_at'       => now(),
                    'updated_at'       => now()
                ]);

                return $miembro;
            });

            $qrPayload = $codigoString;
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrPayload);

            if (!empty($request->correo)) {
                try {
                    \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($request, $qrUrl) {
                        $message->to($request->correo)
                            ->subject('Tu código QR como Miembro Familiar')
                            ->html("
                            <div style='text-align:center; font-family:Arial, sans-serif; color: #333;'>
                                <h2>¡Hola, {$request->nombre_completo}!</h2>
                                <p>Has sido registrado como miembro familiar en el club.</p>
                                <p>Presenta este código QR cuando nuestro personal te lo indique:</p>
                                <div style='margin: 30px 0;'>
                                    <img src='{$qrUrl}' alt='Código QR de Acceso' style='border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);' />
                                </div>
                                <p style='font-size: 12px; color: #666;'>Si no puedes ver la imagen, asegúrate de habilitar las imágenes en tu cliente de correo.</p>
                            </div>
                        ");
                    });
                } catch (\Exception $e) {
                    Log::error('Error enviando correo al familiar: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Miembro familiar y QR creados con éxito',
                'data'    => $result,
                'qr_url'  => $qrUrl
            ], 201);
        } catch (\Exception $e) {
            // === MODO DIAGNÓSTICO EXTREMO (Solo para Desarrollo) ===
            Log::error('Error capturado: ' . $e->getMessage());

            return response()->json([
                'success'    => false,
                'message'    => '¡El código explotó!',
                'error_real' => $e->getMessage(), // <--- AQUÍ ESTÁ EL CHISME
                'archivo'    => $e->getFile(),
                'linea'      => $e->getLine()
            ], 500);
        }
    }


    // ACTUALIZAR
    public function update(Request $request, $id)
    {
        $id_socio = $request->user()->user_id;

        $miembro = MiembrosFamiliares::where('id_miembro', $id)
            ->where('socio_id', $id_socio)
            ->first();

        if (!$miembro) {
            return response()->json(['success' => false, 'message' => 'Miembro no encontrado o no autorizado'], 404);
        }

        $request->validate([
            'nombre_completo'  => 'required|string|max:100',
            'parentesco'       => 'required|in:CONYUGE,HIJO/A,OTRO',
            'fecha_nacimiento' => 'required|date',
            'genero'           => 'required|in:M,F,OTRO',
            'correo' => 'nullable|email|max:255'
        ]);

        try {
            DB::transaction(function () use ($miembro, $request) {
                $miembro->update([
                    'nombre_completo'  => $request->nombre_completo,
                    'parentesco'       => $request->parentesco,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'genero'           => $request->genero,
                    'correo' => $request->correo,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Miembro familiar actualizado correctamente',
                'data'    => $miembro
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar miembro familiar: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Hubo un error al actualizar'], 500);
        }
    }

}
