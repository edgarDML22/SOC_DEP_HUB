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
    public function show(Request $request)
    {
        $id_socio = $request->user()->user_id;

        $id_valido = SocioTitular::where('id_socio', $id_socio)->first();
        if (!$id_valido) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró socio con ese id'
            ], 404);
        }

        // El SoftDelete oculta automáticamente los que tienen deleted_at lleno
        $miembros = MiembrosFamiliares::where('socio_id', $id_socio)->get();
        
        return response()->json($miembros);
    }

    // CREAR 
    public function store(Request $request)
    {
        $id_socio = $request->user()->user_id;

        $request->validate([
            'nombre_completo'  => 'required|string|max:100',
            'parentesco'       => 'required|in:CONYUGE,HIJO/A,OTRO',
            'fecha_nacimiento' => 'required|date',
            'genero'           => 'required|in:M,F,O,OTRO'
        ]);

        do {
            $codigoQR = 'MF' . substr(str_replace('-', '', Str::uuid()), 0, 6);
        } while (MiembrosFamiliares::where('codigo_qr', $codigoQR)->exists());

        try {
            // === INICIA LA TRANSACCIÓN ===
            $miembro = DB::transaction(function () use ($id_socio, $request, $codigoQR) {
                //  Crear el miembro familiar
                return MiembrosFamiliares::create([
                    'socio_id'         => $id_socio,
                    'nombre_completo'  => $request->nombre_completo,
                    'parentesco'       => $request->parentesco,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'genero'           => $request->genero,
                    'codigo_qr'        => $codigoQR,
                ]);
            });
            // === TERMINA LA TRANSACCIÓN ===

            /*  Generar contenido del QR (JSON) e imagen (API Externa) */
            $data = json_encode([
                'codigo_qr' => $codigoQR,
                'tipo' => 'familiar' // Lo identificamos como familiar
            ]);
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($data);

            /* 5. Retornamos la respuesta (Incluyendo la URL del QR generado) */
            return response()->json([
                'success' => true,
                'message' => 'Miembro familiar agregado correctamente',
                'data'    => $miembro,
                'qr_url'  => $qrUrl 
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al agregar miembro familiar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al guardar. Intenta más tarde.'
            ], 500);
        }
    }

    // ACTUALIZAR
    public function update(Request $request, $id)
    {
        $id_socio = $request->user()->user_id;

        // 1. Buscar miembro asegurando que pertenece a este socio
        $miembro = MiembrosFamiliares::where('id_miembro', $id)
                                     ->where('socio_id', $id_socio)
                                     ->first();

        if (!$miembro) {
            return response()->json(['success' => false, 'message' => 'Miembro no encontrado o no autorizado'], 404);
        }

        // 2. Validar
        $request->validate([
            'nombre_completo'  => 'required|string|max:100',
            'parentesco'       => 'required|in:CONYUGE,HIJO/A,OTRO',
            'fecha_nacimiento' => 'required|date',
            'genero'           => 'required|in:M,F,OTRO'
        ]);

        try {
            DB::transaction(function () use ($miembro, $request) {
                // 3. Actualizar registro
                $miembro->update([
                    'nombre_completo'  => $request->nombre_completo,
                    'parentesco'       => $request->parentesco,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'genero'           => $request->genero,
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

    // ELIMINAR
    public function destroy(Request $request, $id)
    {
        $id_socio = $request->user()->user_id;

        $miembro = MiembrosFamiliares::where('id_miembro', $id)
                                     ->where('socio_id', $id_socio)
                                     ->first();

        if (!$miembro) {
            return response()->json(['success' => false, 'message' => 'Miembro no encontrado o no autorizado'], 404);
        }

        try {
            DB::transaction(function () use ($miembro) {
                // El trait SoftDeletes se encargará de llenar la columna deleted_at
                $miembro->delete(); 
            });

            return response()->json([
                'success' => true,
                'message' => 'Miembro familiar eliminado correctamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al eliminar miembro familiar: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Hubo un error al procesar la eliminación'], 500);
        }
    }
}