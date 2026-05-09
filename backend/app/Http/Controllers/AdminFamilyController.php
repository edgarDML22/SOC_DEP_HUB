<?php

namespace App\Http\Controllers;

use App\Models\ActividadPlantilla;
use App\Models\MiembrosFamiliares;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\SocioTitular;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Reservacion;
use App\Models\CodigoQr;
use App\Models\torneos;
use App\Models\EncuentrosTorneo;
// SDH 240 
class AdminFamilyController extends Controller
{

    // --- MÉTODO SHOW ---
    public function show($socioId)
    {
        if ($socioId != auth()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado, el socio no es el titular'
            ], 401);
        }


        $miembros = SocioTitular::query()
            ->join(
                'miembros_familiares as m',
                'm.socio_id',
                '=',
                'socios_titulares.id_socio'
            )
            ->join(
                'codigos_qr as c',
                'c.usuario_id',
                '=',
                'm.id_miembro'
            )
            ->where('socios_titulares.id_socio', $socioId)
            ->where('c.tipo_usuario', 'FAMILIAR')
            ->select(
                'm.id_miembro',
                'm.nombre_completo',
                'm.fecha_nacimiento',
                'c.codigo',
                'm.parentesco',
                'c.tipo_usuario'

            )

            ->orderBy('m.nombre_completo')
            ->get();

        $miembrosConQR = $miembros->map(function ($miembro) {
            $miembro->edad = Carbon::parse(
                $miembro->fecha_nacimiento
            )->age;
            return $miembro;
        });

        return response()->json([
            'success' => true,
            'data' => $miembrosConQR

        ], 200);
    }

    // --- MÉTODO DESTROY ---
    public function destroy(Request $request, $socioId, $miembroId)
    {

        /* $id_socio = $request->user()->user_id; */
        $id_socio = 1;

        if ($socioId != $id_socio) {

            return response()->json([
                'success' => false,
                'message' => 'No autorizado, el socio no es el titular'
            ], 401);
        }

        $miembro = MiembrosFamiliares::where(
            'id_miembro',
            $miembroId
        )
            ->where(
                'socio_id',
                $socioId
            )
            ->first();

        if (!$miembro) {
            return response()->json([
                'success' => false,
                'message' => 'Miembro familiar no encontrado'
            ], 404);
        }

        $codigo_qr = CodigoQr::where(
            'usuario_id',
            $miembroId
        )
            ->where(
                'tipo_usuario',
                'FAMILIAR'
            )
            ->first();

        if (!$codigo_qr) {
            return response()->json([
                'success' => false,
                'message' => 'Código QR no encontrado'
            ], 404);
        }

        try {

            /*
            ---------------------------------------------------
            HARD DELETE
            Menos de 1 hora desde creación QR
            ---------------------------------------------------
            */

            if (
                Carbon::parse($codigo_qr->created_at)
                    ->diffInHours(now()) < 1
            ) {
                DB::transaction(function () use ($miembro, $codigo_qr) {

                    // Hard delete QR
                    $codigo_qr->delete();

                    // Hard delete familiar
                    $miembro->forceDelete();
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Miembro eliminado permanentemente'
                ], 200);
            }

            /*
            ---------------------------------------------------
            VALIDAR RESERVAS ACTIVAS
            ---------------------------------------------------
            */

            $available = Reservacion::where(
                'id_socio_titular',
                $id_socio
            )
                ->whereRaw("
            EXISTS (
                SELECT 1
                FROM jsonb_array_elements(
                    acompanantes_draft
                ) elem
                WHERE (elem->>'id')::int = ?
            )
        ", [$miembro->id_miembro])
                ->where(function ($q) {
                    $q->where(
                        'estatus_operativo',
                        '!=',
                        'CANCELADA'
                    )
                        ->orWhereNull(
                            'estatus_operativo'
                        );
                })
                ->exists();

            /*
            ---------------------------------------------------
            VALIDAR TORNEOS
            ---------------------------------------------------
            */

            $torneos = EncuentrosTorneo::where(
                'competidor_1_id',
                $miembroId
            )
                ->orWhere(
                    'competidor_2_id',
                    $miembroId
                )
                ->exists();

            if ($available || $torneos) {

                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar porque tiene reservas o torneos activos'
                ], 409);
            }

            /*
            ---------------------------------------------------
            SOFT DELETE
            ---------------------------------------------------
            */

            DB::transaction(function () use ($miembro, $codigo_qr) {
                // Desactivar QR
                $codigo_qr->update([
                    'estatus' => 'INACTIVO',
                    'deleted_at' => now()
                ]);
                // Soft delete familiar
                $miembro->delete();
            });
            return response()->json([
                'success' => true,
                'message' => 'Miembro eliminado correctamente'
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // CREAR 
    public function store(Request $request)
    {
        $id_socio = $request->user()->user_id;

        // VALIDACION DATOS FRONTEND
        $request->validate([
            'nombre_completo' => 'required|string|max:100',
            'parentesco' => 'required|in:CONYUGE,HIJO/A,OTRO',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|in:M,F,O,OTRO',
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
                    'socio_id' => $id_socio,
                    'nombre_completo' => $request->nombre_completo,
                    'parentesco' => $request->parentesco,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'genero' => $request->genero,
                    'correo' => $request->correo,
                ]);

                //
                DB::table('codigos_qr')->insert([
                    'codigo' => $codigoString,
                    'usuario_id' => $miembro->id_miembro,
                    'tipo_usuario' => 'FAMILIAR',
                    'estatus' => 'EXPIRADO',
                    'fecha_activacion' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
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
                'data' => $result,
                'qr_url' => $qrUrl
            ], 201);

        } catch (\Exception $e) {
            // === MODO DIAGNÓSTICO EXTREMO (Solo para Desarrollo) ===
            Log::error('Error capturado: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '¡El código explotó!',
                'error_real' => $e->getMessage(), // <--- AQUÍ ESTÁ EL CHISME
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ], 500);
        }
    }


    // ACTUALIZAR
    public function update(Request $request, $id_socio, $id_miembro)
    {
        $id_socio = $request->user()->user_id;
        //$id_socio = 1;
        $miembro = MiembrosFamiliares::where('id_miembro', $id_miembro)
            ->where('socio_id', $id_socio)
            ->first();

        if (!$miembro) {
            return response()->json(['success' => false, 'message' => 'Miembro no encontrado o no autorizado'], 404);
        }

        $request->validate([
            'nombre_completo' => 'required|string|max:100',
            'parentesco' => 'required|in:CONYUGE,HIJO/A,OTRO',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|in:M,F,OTRO',
            'correo' => 'nullable|email|max:255'
        ]);

        try {
            DB::transaction(function () use ($miembro, $request) {
                $miembro->update([
                    'nombre_completo' => $request->nombre_completo,
                    'parentesco' => $request->parentesco,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'genero' => $request->genero,
                    'correo' => $request->correo,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Miembro familiar actualizado correctamente',
                'data' => $miembro
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al actualizar miembro familiar: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Hubo un error al actualizar'], 500);
        }
    }



}
