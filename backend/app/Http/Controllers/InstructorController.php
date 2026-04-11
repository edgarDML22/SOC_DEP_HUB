<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\SesionActiva;
use App\Models\Instructor;
use Carbon\Carbon;

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
                'nombre_completo'    => $instructor->nombre_completo,
                'correo_electronico' => $instructor->correo_electronico,
                'telefono'           => $instructor->telefono,
                'estatus_cuenta'     => $instructor->estatus,
                'fecha_nacimiento'   => $instructor->fecha_nacimiento,
                'fecha_contratacion' => $instructor->fecha_contratacion,
                'rol'                => 'Instructor',
            ]
        ], 200);
    }
}
