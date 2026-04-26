<?php

namespace App\Http\Controllers;
use App\Models\RegistrosLudoteca;
use Illuminate\Http\Request;
use App\Models\MiembrosFamiliares;
use App\Models\SocioTitular;
use App\Models\User;
use App\Models\TurnosLudoteca;
class LudotecaController extends Controller
{
    //rgresa lista de ludoteca
    public function validarTutor(Request $request)
    {
        $request->validate([
            'id_socio' => 'required',
        ]);
        //si es gerente, mostrar todos los registros
        $type = User::where('id', $request->id_socio)->first();
        if ($type->rol == 'gerente' || $type->rol == 'subgerente') {
            $registros = RegistrosLudoteca::with(['adultoIngreso', 'menor'])->get();

            if ($registros->count() == 0) {
                return response()->json([
                    'message' => 'No se encontraron registros activos en la ludoteca',
                ], 404);
            } else {
                return response()->json([
                    'data' => $registros->map(function ($registro) {
                        return [
                            'socio' => $registro->adultoIngreso->nombre_completo,
                            'id_menor' => $registro->menor->id_miembro,
                            'menor' => $registro->menor->nombre_completo,
                            'hora_ingreso' => $registro->hora_ingreso,
                            'hora_limite' => $registro->hora_limite,
                            'estatus_visita' => $registro->estatus_visita,
                            'rol' => 'gerente',
                            'id_registro' => $registro->id_registro,

                        ];
                    })
                ]);
            }
        }
        if ($type->rol == 'instructor') {

            $registros = RegistrosLudoteca::with(['adultoIngreso', 'menor'])
                ->whereDate('hora_ingreso', today())
                ->get();


            $turno = TurnosLudoteca::where('id_instructor', $request->id_socio)
                ->where('fecha', today())
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'turno' => [
                        'hora_inicio' => $turno->hora_inicio,
                        'hora_fin' => $turno->hora_fin
                    ],
                    'estancias' => $registros->map(function ($registro) {
                        return [
                            'id_registro' => $registro->id_registro,
                            'id_socio' => $registro->id_adulto_ingreso,
                            'nombre_nino' => $registro->menor->nombre_completo,
                            'nombre_tutor' => $registro->adultoIngreso->nombre_completo,
                            'estatus' => $registro->estatus_ludoteca,
                            'hora_ingreso' => $registro->hora_ingreso,
                            'hora_salida' => $registro->hora_egreso
                        ];
                    })
                ]
            ]);
        }
        //si es tutor, mostrar solo sus registros
        $tutor = RegistrosLudoteca::where('id_adulto_ingreso', $request->id_socio)->first();
        if (!$tutor) {
            return response()->json([
                'message' => 'Tutor no encontrado',
            ], 404);
        }
        //si es tutor, mostrar solo sus registros
        $registros = RegistrosLudoteca::with(['adultoIngreso', 'menor'])
            ->where('id_adulto_ingreso', $request->id_socio)
            ->get();

        if ($registros->count() == 0) {
            return response()->json([
                'message' => 'No se encontraron registros activos',
            ], 404);
            //Si es instructor 
        } else {
            return response()->json([
                'data' => $registros->map(function ($registro) {
                    return [
                        'socio' => $registro->adultoIngreso->nombre_completo,
                        'menor' => $registro->menor->nombre_completo,
                        'hora_ingreso' => $registro->hora_ingreso,
                        'hora_limite' => $registro->hora_limite,
                        'estatus_visita' => $registro->estatus_visita,
                        'rol' => 'tutor',
                        'id_registro' => $registro->id_registro,
                        'hora_egreso' => $registro->hora_egreso,
                        'id_adulto_egreso' => $registro->id_adulto_egreso,
                        'id_instructor_egreso' => $registro->id_instructor_egreso




                    ];
                })
            ]);
        }



    }
}