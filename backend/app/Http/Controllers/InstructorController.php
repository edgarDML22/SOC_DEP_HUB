<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SesionActiva;
use App\Models\Instructor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstructorController extends Controller
{
    /**
     * Obtener los datos del Dashboard dinámico del Instructor
     */
    public function getDashboardData(Request $request)
    {
        $user = Auth::user();

        // Validar acceso
        if ($user->rol !== 'instructor') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. No eres instructor.'
            ], 403);
        }

        $instructorId = $user->user_id;

        // Fecha y hora base (ajustar timezone general de la app si es necesario, usando America/Mexico_City)
        $now = Carbon::now('America/Mexico_City');
        $todayDate = $now->toDateString();
        $nowPlus2 = $now->copy()->addHours(2);

        // Consultar sesiones activas del instructor programadas para hoy
        $sesionesHoy = SesionActiva::with(['actividadPlantilla.disciplina', 'actividadPlantilla.espacioFisico'])
            ->whereHas('actividadPlantilla', function ($query) use ($instructorId) {
                $query->where('id_instructor', $instructorId);
            })
            ->whereDate('fecha_sesion', $todayDate)
            ->get();

        // Inicializar cálculos de estadísticas
        $stats = [
            'sesionesHoy' => $sesionesHoy->count(),
            'proximas2Horas' => 0,
            'pendientes' => 0,
            'totalInscritos' => 0
        ];

        $todaySessionsList = [];

        foreach ($sesionesHoy as $sesion) {
            $plantilla = $sesion->actividadPlantilla;
            $estatusList = strtoupper($sesion->estatus_sesion);

            // 1. Estadísitica: Pendientes
            if (!in_array($estatusList, ['FINALIZADA', 'CANCELADA'])) {
                $stats['pendientes']++;
            }

            // 2. Estadística: Total Inscritos
            // El campo cantidad_inscritos suele estar en la sesión activa
            $stats['totalInscritos'] += ($sesion->cantidad_inscritos ?? 0);

            // 3. Estadística: Próximas 2 horas
            // Formar el datetime exacto de inicio de esta sesión
            $horaInicioReal = Carbon::parse($todayDate . ' ' . $plantilla->hora_inicio, 'America/Mexico_City');

            // Verificamos si la hora de inicio está entre justo ahora y las próximas 2 horas
            // Tambien es válido si la sesión no ha finalizado y su hora de fin todavia está en ese rango
            if ($horaInicioReal->greaterThanOrEqualTo($now) && $horaInicioReal->lessThanOrEqualTo($nowPlus2)) {
                $stats['proximas2Horas']++;
            }

            // Mapeamos el color/style del badge
            $statusType = 'info'; // Default
            switch ($estatusList) {
                case 'FINALIZADA':
                    $statusType = 'success-dark';
                    break;
                case 'DISPONIBLE':
                case 'ACTIVA':
                    $statusType = 'success';
                    break;
                case 'EN_CURSO':
                    $statusType = 'info';
                    break;
                case 'LLENO':
                case 'AL_LIMITE':
                    $statusType = 'warning';
                    break;
                case 'CANCELADA':
                    $statusType = 'danger'; // Necesitará un estilo badge-danger en frontend
                    break;
            }

            // Formatear y añadir al listado que leerá el front
            $todaySessionsList[] = [
                'id' => $sesion->id_sesion,
                'startTime' => Carbon::parse($plantilla->hora_inicio)->format('H:i'),
                'endTime' => Carbon::parse($plantilla->hora_fin)->format('H:i'),
                'client' => $plantilla->disciplina->nombre_disciplina ?? 'Clase Especial',
                'location' => $plantilla->espacioFisico->nombre_espacio ?? 'Área General',
                'status' => ucfirst(strtolower(str_replace('_', ' ', $estatusList))),
                'statusType' => $statusType,
                'rawStartTime' => $plantilla->hora_inicio // Útil para ordenar 
            ];
        }

        // Ordenar la lista cronológicamente
        usort($todaySessionsList, function ($a, $b) {
            return strcmp($a['rawStartTime'], $b['rawStartTime']);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'todaySessions' => $todaySessionsList,
            ]
        ], 200);
    }

    public function getProfileData(Request $request)
    {
        $user = Auth::user();

        // Validar acceso
        if ($user->rol !== 'instructor') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. No eres instructor.'
            ], 403);
        }

        $instructorId = $user->user_id;

        // Consultar perfil del instructor con join a usuarios para obtener correo
        $instructor = DB::table('instructores as i')
            ->join('usuarios as u', 'i.id_usuario', '=', 'u.id')
            ->select(
                'i.id_instructor',
                'i.nombre as nombre_completo',
                'i.telefono',
                'i.estatus',
                'i.fecha_nacimiento',
                'i.fecha_contratacion',
                'u.correo as correo_electronico',
                'u.rol'
            )
            ->where('i.id_instructor', $instructorId)
            ->first();

        if (!$instructor) {
            return response()->json([
                'success' => false,
                'message' => 'Instructor no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nombre_completo' => $instructor->nombre_completo,
                'correo_electronico' => $instructor->correo_electronico,
                'telefono' => $instructor->telefono,
                'estatus_cuenta' => $instructor->estatus,
                'fecha_nacimiento' => $instructor->fecha_nacimiento,
                'fecha_contratacion' => $instructor->fecha_contratacion,
                'rol' => 'Instructor',
            ]
        ], 200);
    }

    public function getAllInstructors(Request $request)
    {
        // Validar acceso
        $user = $request->user();
        if (!in_array($user->rol, ['gerente', 'subgerente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado.'
            ], 403);
        }

        $instructores = Instructor::with('disciplinas')->orderBy('estatus', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $instructores
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $admin = Auth::user();

        if ($admin->rol !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. No eres administrador.'
            ], 403);
        }

        $instructor = Instructor::with('disciplinas')->find($id);

        if (!$instructor) {
            return response()->json([
                'success' => false,
                'message' => 'Instructor no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $instructor
        ], 200);
    }

    public function store(Request $request)
    {
        $admin = Auth::user();

        // Validar acceso
        if ($admin->rol !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. No eres administrador.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // 1. Crear el usuario para el login
            $nombre = $request->input('nombre_completo');
            $correoGenerado = strtolower(explode(' ', $nombre)[0]) . '_' . Str::random(4) . '@socdep.com';

            $newUser = User::create([
                'name' => $nombre,
                'email' => $correoGenerado,
                'password' => Hash::make('password'),
                'rol' => 'instructor',
                'user_id' => 0 // Temporalmente, actualizaremos esto despues
            ]);

            // 2. Crear el instructor
            $instructor = Instructor::create([
                'id_usuario' => $newUser->id,
                'nombre_completo' => $nombre,
                'telefono' => $request->input('telefono'),
                'estatus' => $request->input('estatus', 'ACTIVO'),
                'fecha_contratacion' => $request->input('fecha_contratacion'),
                'fecha_nacimiento' => $request->input('fecha_nacimiento')
            ]);

            // 3. Ligar user_id en users
            $newUser->user_id = $instructor->id_instructor;
            $newUser->save();

            // 4. Sincronizar disciplinas
            if ($request->has('disciplinas')) {
                $instructor->disciplinas()->sync($request->input('disciplinas'));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $instructor->load('disciplinas')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear instructor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $admin = Auth::user();

        // Validar acceso
        if ($admin->rol !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. No eres administrador.'
            ], 403);
        }

        $instructor = Instructor::find($id);

        if (!$instructor) {
            return response()->json([
                'success' => false,
                'message' => 'Instructor no encontrado'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Actualizar datos del instructor
            $instructor->update($request->only([
                'nombre_completo',
                'telefono',
                'estatus',
                'fecha_contratacion',
                'fecha_nacimiento'
            ]));

            // Si el nombre cambió, actualizar en users
            if ($request->has('nombre_completo')) {
                $user = User::where('id', $instructor->id_usuario)->first();
                if ($user) {
                    $user->name = $request->input('nombre_completo');
                    $user->save();
                }
            }

            // Actualizar disciplinas
            if ($request->has('disciplinas')) {
                $instructor->disciplinas()->sync($request->input('disciplinas'));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $instructor->load('disciplinas')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar instructor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $admin = Auth::user();

        // Validar acceso
        if ($admin->rol !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. No eres administrador.'
            ], 403);
        }

        $instructor = Instructor::find($id);

        if (!$instructor) {
            return response()->json([
                'success' => false,
                'message' => 'Instructor no encontrado'
            ], 404);
        }

        // Borrado lógico (cambio de estatus)
        $instructor->estatus = 'INACTIVO';
        $instructor->save();

        return response()->json([
            'success' => true,
            'message' => 'Instructor dado de baja temporalmente/inactivado correctamente'
        ], 200);
    }


}
