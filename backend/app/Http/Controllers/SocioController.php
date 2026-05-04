<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SocioTitular;
use App\Models\User;
use App\Notifications\SancionAsignadaNotification;
use App\Notifications\SancionLevantadaNotification;

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

    // --- MÉTODO SHOW ---
    public function show(Request $request, $id)
    {
        // Cargamos el socio con su código QR activo
        $socio = SocioTitular::with('codigoQrActivo')->find($id);

        if (!$socio)
            return response()->json(['success' => false, 'message' => 'Socio no encontrado'], 404);

        // Adjuntamos el código para el frontend
        $socio->codigo_qr = $socio->codigoQrActivo ? $socio->codigoQrActivo->codigo : null;

        return response()->json(['success' => true, 'data' => $socio], 200);
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
            $updateData = array_filter($request->only([
                'nombre_completo',
                'correo_electronico',
                'tipo_socio',
                'modalidad_plan',
                'estatus_cuenta',
                'estatus_penalizacion',
                'contador_no_shows',
                'retrasos_ludoteca',
                'fecha_nacimiento',
                'genero',
            ]), fn($value) => !is_null($value));

            // Lógica de estatus_cuenta (pagos — independiente de penalizaciones)
            if ($request->input('estatus_cuenta') === 'AL_CORRIENTE') {
                $updateData['contador_no_shows'] = 0;
            }

            // Lógica de estatus_penalizacion con fechas por servicio
            if ($request->has('estatus_penalizacion')) {
                $nuevoPenalizacion = $request->input('estatus_penalizacion');
                $diasLudoteca = (int) $request->input('dias_penalizacion_ludoteca', 7);
                $diasReserva = (int) $request->input('dias_penalizacion_reserva', 7);
                $ahora = now('America/Mexico_City');

                if ($nuevoPenalizacion === 'SIN_PENALIZACION') {
                    $updateData['fecha_fin_penalizacion_ludoteca'] = null;
                    $updateData['fecha_fin_penalizacion_reserva'] = null;
                    $updateData['contador_no_shows'] = 0;
                } elseif ($nuevoPenalizacion === 'PENALIZADO_LUDOTECA') {
                    $updateData['fecha_fin_penalizacion_ludoteca'] = $ahora->copy()->addDays($diasLudoteca)->startOfDay();
                    $updateData['fecha_fin_penalizacion_reserva'] = null;
                } elseif ($nuevoPenalizacion === 'PENALIZADO_RESERVA') {
                    $updateData['fecha_fin_penalizacion_reserva'] = $ahora->copy()->addDays($diasReserva)->startOfDay();
                    $updateData['fecha_fin_penalizacion_ludoteca'] = null;
                } elseif ($nuevoPenalizacion === 'PENALIZADO_AMBOS') {
                    $updateData['fecha_fin_penalizacion_ludoteca'] = $ahora->copy()->addDays($diasLudoteca)->startOfDay();
                    $updateData['fecha_fin_penalizacion_reserva'] = $ahora->copy()->addDays($diasReserva)->startOfDay();
                }
            }

            $socio->update($updateData);

            // Notificaciones al socio cuando el admin cambia el estatus_penalizacion
            if ($request->has('estatus_penalizacion')) {
                $socio->refresh();
                $nuevoPenalizacion = $socio->estatus_penalizacion;

                if (in_array($nuevoPenalizacion, ['PENALIZADO_RESERVA', 'PENALIZADO_LUDOTECA', 'PENALIZADO_AMBOS'])) {
                    $socio->notify(new SancionAsignadaNotification(
                        estatus_penalizacion: $nuevoPenalizacion,
                        fecha_fin_reserva: $socio->fecha_fin_penalizacion_reserva?->toDateString(),
                        fecha_fin_ludoteca: $socio->fecha_fin_penalizacion_ludoteca?->toDateString(),
                        nombre_socio: $socio->nombre_completo,
                    ));
                } elseif ($nuevoPenalizacion === 'SIN_PENALIZACION') {
                    $socio->notify(new SancionLevantadaNotification(
                        nombre_socio: $socio->nombre_completo,
                        motivo: 'manual',
                    ));
                }
            }

            // Sincronizar con la tabla 'users' si hay campos en común (email)
            if ($request->has('correo_electronico') && $request->input('correo_electronico')) {
                $usuarioLogin = User::where('user_id', $socio->id_socio)
                    ->where('rol', 'socio_titular')
                    ->first();
                if ($usuarioLogin) {
                    $usuarioLogin->email = $request->input('correo_electronico');
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
                'file' => $e->getFile()
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


    public function updateEstatusController(Request $request, $id)
    {
        $socio = SocioTitular::find($id);
        if (!$socio) {
            return response()->json([
                'success' => false,
                'message' => 'Socio no encontrado'
            ], 404);
        }
        $response = $this->validateStauts($request, $socio);
        if ($response == false) {
            return response()->json([
                'success' => false,
                'message' => 'Estatus no valido'
            ], 403);
        }
        SocioTitular::where('id_socio', $id)->update([
            'estatus_cuenta' => $request->nuevo_estatus
        ]);
        return response()->json([
            'nuevo_estatus' => $request->nuevo_estatus,
            'success' => true,
            'message' => 'Estatus actualizado correctamente',

        ], 200);


    }

    public function validateStauts($request, $socio)
    {
        if ($request->nuevo_estatus != 'SUSPENDIDO' && $request->nuevo_estatus != 'AL_CORRIENTE') {
            return false;
        }
        if ($request->nuevo_estatus == 'SUSPENDIDO') {
            if ($socio->estatus_cuenta == 'AL_CORRIENTE' || $socio->estatus_cuenta == 'MOROSO') {
                return true;
            } else {
                return false;
            }
        } else {
            if ($socio->estatus_cuenta == 'SUSPENDIDO') {
                return true;
            } else {
                return false;
            }
        }

    }
}
