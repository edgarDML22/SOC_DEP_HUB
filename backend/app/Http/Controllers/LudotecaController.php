<?php

namespace App\Http\Controllers;
use App\Models\RegistrosLudoteca;
use Illuminate\Http\Request;
use App\Models\MiembrosFamiliares;
use App\Models\SocioTitular;
use App\Models\User;
use App\Models\TurnosLudoteca;
use App\Models\Instructor;
class LudotecaController extends Controller
{
    //rgresa lista de ludoteca
    public function validarTutor(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $hoy = now('America/Mexico_City')->toDateString();
        $inicioDia = $hoy . ' 00:00:00';
        $finDia = $hoy . ' 23:59:59';

        // Gerente / Subgerente
        if (in_array($user->rol, ['gerente', 'subgerente'])) {
            $registros = RegistrosLudoteca::with(['adultoIngreso', 'menor'])
                ->where(function ($query) use ($inicioDia, $finDia) {
                    $query->whereBetween('hora_ingreso', [$inicioDia, $finDia])
                          ->orWhereIn('estatus_ludoteca', ['ACTIVA', 'INACTIVO']);
                })
                ->get();

            if ($registros->isEmpty()) {
                return response()->json(['message' => 'No se encontraron registros'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'turno' => [
                        'hora_inicio' => '00:00:00',
                        'hora_fin' => '23:59:59'
                    ],
                    'estancias' => $registros->map(function ($registro) {
                        return [
                            'socio' => optional($registro->adultoIngreso)->nombre_completo ?? 'N/A',
                            'id_menor' => $registro->menor->id_miembro,
                            'nombre_nino' => $registro->menor->nombre_completo, // Cambiado para coincidir con el store
                            'nombre_tutor' => optional($registro->adultoIngreso)->nombre_completo ?? 'N/A', // Cambiado para coincidir con el store
                            'hora_ingreso' => $registro->hora_ingreso,
                            'hora_limite' => $registro->hora_limite,
                            'estatus' => $registro->estatus_ludoteca, // Cambiado para coincidir con el store
                            'rol' => 'gerente',
                            'id_registro' => $registro->id_registro,
                        ];
                    })
                ]
            ]);
        }

        // Instructor
        if ($user->rol == 'instructor') {
            $registros = RegistrosLudoteca::with(['adultoIngreso', 'menor'])
                ->where(function ($query) use ($inicioDia, $finDia) {
                    $query->whereBetween('hora_ingreso', [$inicioDia, $finDia])
                        ->orWhereIn('estatus_ludoteca', ['ACTIVA', 'INACTIVO']);
                })
                ->has('menor')
                ->get();

            $turno = TurnosLudoteca::where('id_instructor', $user->user_id)
                ->where('fecha', $hoy)
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'turno' => [
                        'hora_inicio' => $turno ? $turno->hora_inicio : null,
                        'hora_fin' => $turno ? $turno->hora_fin : null
                    ],
                    'estancias' => $registros->map(function ($registro) {
                        return [
                            'id_registro' => $registro->id_registro,
                            'id_socio' => $registro->id_adulto_ingreso,
                            'nombre_nino' => $registro->menor->nombre_completo,
                            'nombre_tutor' => optional($registro->adultoIngreso)->nombre_completo ?? 'Sin tutor asignado',
                            'estatus' => $registro->estatus_ludoteca,
                            'hora_ingreso' => $registro->hora_ingreso,
                            'hora_salida' => $registro->hora_egreso,
                            'hora_limite' => $registro->hora_limite
                        ];
                    })
                ]
            ]);
        }

        // Socio (Titular o Miembro Familiar)
        if (in_array($user->rol, ['socio_titular', 'miembro_familiar'])) {
            // Obtenemos el ID del titular para filtrar por familia
            $id_titular = $user->user_id;
            if ($user->rol == 'miembro_familiar') {
                $id_titular = MiembrosFamiliares::where('id_miembro', $user->user_id)->value('socio_id');
            }

            // Obtenemos los IDs de todos los menores de esta familia
            $ids_menores = MiembrosFamiliares::where('socio_id', $id_titular)->pluck('id_miembro');

            $registros = RegistrosLudoteca::with(['adultoIngreso', 'menor'])
                ->whereIn('id_menor', $ids_menores)
                ->whereBetween('hora_ingreso', [$inicioDia, $finDia])
                ->get();

            if ($registros->isEmpty()) {
                return response()->json(['message' => 'No hay registros activos para tu familia hoy', 'data' => []], 200);
            }

            return response()->json([
                'data' => $registros->map(function ($registro) {
                    return [
                        'socio' => optional($registro->adultoIngreso)->nombre_completo ?? 'Tutor',
                        'menor' => $registro->menor->nombre_completo,
                        'hora_ingreso' => $registro->hora_ingreso,
                        'hora_limite' => $registro->hora_limite,
                        'estatus_visita' => $registro->estatus_ludoteca,
                        'rol' => 'tutor',
                        'id_registro' => $registro->id_registro,
                        'hora_egreso' => $registro->hora_egreso,
                    ];
                })
            ]);
        }

        return response()->json(['message' => 'Rol no autorizado'], 403);
    }
}