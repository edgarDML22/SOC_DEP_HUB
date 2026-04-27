<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SocioTitular;
use App\Models\User;

class SocioController extends Controller
{
    /**
     * Listado de todos los socios titulares
     * 
     * GET /api/v1/socios
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->rol, ['gerente', 'subgerente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado.'
            ], 403);
        }

        // Retornar los socios titulares ordenados por estatus (por ejemplo, AL_CORRIENTE primero)
        $socios = SocioTitular::orderByRaw("
            CASE 
                WHEN estatus_cuenta = 'AL_CORRIENTE' THEN 1
                WHEN estatus_cuenta = 'MOROSO' THEN 2
                WHEN estatus_cuenta = 'SUSPENDIDO' THEN 3
                ELSE 4
            END
        ")->orderBy('nombre_completo', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $socios
        ], 200);
    }

    /**
     * Detalles de un socio titular específico
     * 
     * GET /api/v1/socios/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->rol, ['gerente', 'subgerente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado.'
            ], 403);
        }

        $socio = SocioTitular::with('miembrosFamiliares')->find($id);

        if (!$socio) {
            return response()->json([
                'success' => false,
                'message' => 'Socio no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $socio
        ], 200);
    }

    /**
     * Actualizar los datos y penalizaciones de un socio titular
     * 
     * PUT /api/v1/socios/update/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->rol, ['gerente', 'subgerente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado.'
            ], 403);
        }

        $socio = SocioTitular::find($id);

        if (!$socio) {
            return response()->json([
                'success' => false,
                'message' => 'Socio no encontrado'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Datos base para actualizar
            $updateData = $request->only([
                'nombre_completo',
                'correo_electronico',
                'tipo_socio',
                'modalidad_plan',
                'estatus_cuenta',
                'contador_no_shows',
                'retrasos_ludoteca',
                'fecha_nacimiento',
                'genero'
            ]);

            // Lógica específica para penalización
            if ($request->input('estatus_cuenta') === 'PENALIZADO') {
                if (!$socio->fecha_fin_penalizacion || $socio->estatus_cuenta !== 'PENALIZADO') {
                    // Usar Carbon directamente para mayor precisión
                    // $updateData['fecha_fin_penalizacion'] = \Carbon\Carbon::now('America/Mexico_City')->addDays(7)->startOfDay();
                    $updateData['fecha_fin_penalizacion'] = \Carbon\Carbon::now('America/Mexico_City')->addMinutes(5);
                }
            } elseif ($request->input('estatus_cuenta') === 'AL_CORRIENTE') {
                $updateData['fecha_fin_penalizacion'] = null;
                $updateData['contador_no_shows'] = 0;
            }

            $socio->update($updateData);

            // Sincronizar con la tabla 'users' si hay campos en común (email)
            if ($request->has('correo_electronico')) {
                $usuarioLogin = User::where('user_id', $socio->id_socio)
                    ->where('rol', 'socio_titular')
                    ->first();
                if ($usuarioLogin) {
                    $usuarioLogin->email = $request->input('correo_electronico');
                    // NOTA: No se actualiza 'name' porque la columna no existe en la tabla 'users'.
                    $usuarioLogin->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $socio->load('miembrosFamiliares')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error("Error en SocioController@update: " . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar socio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar socios y familiares para autocompletado en reservaciones u otros flujos.
     * 
     * GET /api/v1/socios/search?query=...
     */
    public function search(Request $request)
    {
        $queryParam = $request->query('query', '');

        // Validación inicial para no hacer búsquedas pesadas vacías
        if (empty($queryParam) || strlen($queryParam) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ], 200);
        }

        // 1. Buscar en socios titulares
        // Se utiliza ILIKE asumiendo la conexión de PostgreSQL configurada en Neon
        $titulares = DB::table('socios_titulares')
            ->select('id_socio as id', 'nombre_completo as nombre', 'numero_accion as numero_socio')
            ->where(function ($q) use ($queryParam) {
                $q->where('nombre_completo', 'ILIKE', "%{$queryParam}%")
                    ->orWhere('numero_accion', 'ILIKE', "%{$queryParam}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($item) {
                // Formateamos como solicita el frontend
                $item->foto_perfil = null; // Placeholder: Puedes llenarlo si tienes columna como foto_url
                $item->tipo_perfil = 'socio_titular';
                return $item;
            });

        // 2. Buscar en miembros familiares
        $familiares = DB::table('miembros_familiares as mf')
            ->join('socios_titulares as st', 'mf.socio_id', '=', 'st.id_socio')
            ->select('mf.id_miembro as id', 'mf.nombre_completo as nombre', 'st.numero_accion as numero_socio')
            ->where('mf.nombre_completo', 'ILIKE', "%{$queryParam}%")
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->foto_perfil = null; // Placeholder
                $item->tipo_perfil = 'miembro_familiar';
                return $item;
            });

        // Combinamos resultados, ordenamos alfabéticamente y limitamos a 15 sugerencias
        $resultados = $titulares->merge($familiares)
            ->sortBy('nombre')
            ->values()
            ->take(15);

        return response()->json([
            'success' => true,
            'data' => $resultados
        ], 200);
    }
}
