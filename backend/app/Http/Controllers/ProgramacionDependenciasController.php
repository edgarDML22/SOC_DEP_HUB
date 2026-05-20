<?php

namespace App\Http\Controllers;

use App\Models\Disciplina;
use App\Models\EspacioFisico;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/programacion/dependencias
 *
 * Retorna en una sola llamada toda la data estática necesaria para inicializar
 * el Wizard de programación de actividades en el frontend. Evita el patrón N+1
 * de peticiones HTTP que se daría si el Wizard consultara disciplinas, espacios
 * e instructores por separado.
 *
 * Estructura del JSON de respuesta:
 * {
 *   "disciplinas": [
 *     {
 *       "id_disciplina": 1,
 *       "nombre_disciplina": "Yoga",
 *       "estatus": "ACTIVO"
 *     }
 *   ],
 *   "espacios": [
 *     {
 *       "id_espacio": 1,
 *       "nombre_espacio": "Sala A",
 *       "capacidad_maxima": 20,
 *       "es_clase_programada": true,
 *       "es_reserva_on_demand": false,
 *       "es_uso_libre": false,
 *       "estatus": "ACTIVO",
 *       "disciplinas_ids": [1, 3, 5]   // IDs de disciplinas soportadas (de espacio_disciplina)
 *     }
 *   ],
 *   "instructores": [
 *     {
 *       "id_instructor": 7,
 *       "nombre_completo": "Juan Pérez",
 *       "correo_electronico": "juan@example.com",
 *       "estatus": "ACTIVO",
 *       "disciplinas_ids": [1, 3]       // IDs de disciplinas habilitadas (de instructor_disciplina)
 *     }
 *   ]
 * }
 *
 * Reglas de negocio aplicadas:
 *  - Disciplinas: solo estatus = 'ACTIVO'
 *  - Espacios:    solo estatus = 'ACTIVO' y es_clase_programada = true
 *  - Instructores: solo estatus = 'ACTIVO' (excluye INACTIVO y BAJA_TEMPORAL,
 *                  que son los estados que impiden impartir clases)
 */
class ProgramacionDependenciasController extends Controller
{
    public function index(): JsonResponse
    {
        $disciplinas = Disciplina::where('estatus', 'ACTIVO')
            ->select('id_disciplina', 'nombre_disciplina', 'estatus')
            ->orderBy('nombre_disciplina')
            ->get();

        // Carga los IDs de disciplinas por espacio en una sola query via eager loading
        $espacios = EspacioFisico::where('estatus', 'ACTIVO')
            ->where('es_clase_programada', true)
            ->select(
                'id_espacio',
                'nombre_espacio',
                'capacidad_maxima',
                'es_clase_programada',
                'es_reserva_on_demand',
                'es_uso_libre',
                'estatus'
            )
            ->with(['disciplinas:id_disciplina'])
            ->orderBy('nombre_espacio')
            ->get()
            ->map(fn($espacio) => [
                'id_espacio'          => $espacio->id_espacio,
                'nombre_espacio'      => $espacio->nombre_espacio,
                'capacidad_maxima'    => $espacio->capacidad_maxima,
                'es_clase_programada' => $espacio->es_clase_programada,
                'es_reserva_on_demand'=> $espacio->es_reserva_on_demand,
                'es_uso_libre'        => $espacio->es_uso_libre,
                'estatus'             => $espacio->estatus,
                'disciplinas_ids'     => $espacio->disciplinas->pluck('id_disciplina')->values(),
            ]);

        // Solo instructores ACTIVOS pueden impartir clases.
        // INACTIVO = suspensión administrativa; BAJA_TEMPORAL = baja por período definido.
        $instructores = Instructor::where('estatus', 'ACTIVO')
            ->select('id_instructor', 'nombre_completo', 'correo_electronico', 'estatus')
            ->with(['disciplinas:id_disciplina'])
            ->orderBy('nombre_completo')
            ->get()
            ->map(fn($instructor) => [
                'id_instructor'      => $instructor->id_instructor,
                'nombre_completo'    => $instructor->nombre_completo,
                'correo_electronico' => $instructor->correo_electronico,
                'estatus'            => $instructor->estatus,
                'disciplinas_ids'    => $instructor->disciplinas->pluck('id_disciplina')->values(),
            ]);

        return response()->json([
            'disciplinas'  => $disciplinas,
            'espacios'     => $espacios,
            'instructores' => $instructores,
        ]);
    }
}
