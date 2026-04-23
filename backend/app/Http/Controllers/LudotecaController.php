<?php

namespace App\Http\Controllers;
use App\Models\RegistrosLudoteca;
use Illuminate\Http\Request;
use App\Models\MiembrosFamiliares;
use App\Models\SocioTitular;
use App\Models\User;
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



                    ];
                })
            ]);
        }



    }
}