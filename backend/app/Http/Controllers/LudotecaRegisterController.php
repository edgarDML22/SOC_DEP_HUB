<?php

namespace App\Http\Controllers;

use App\Models\MiembrosFamiliares;
use App\Models\RegistrosLudoteca;
use Illuminate\Http\Request;
use App\Models\SocioTitular;
use App\Models\MongoDB\RegistroLudotecaMongo;
use Carbon\Carbon;

class LudotecaRegisterController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_miembro' => 'required|exists:miembros_familiares,id_miembro',
            'id_socio' => 'required|exists:socios_titulares,id_socio',

        ]);
        //VALIDACION 1: MODALIDAD DE PLAN DEL SOCIO
        $modalidad_plan = SocioTitular::where('id_socio', $request->id_socio)->first();

        if ($modalidad_plan->tipo_socio != 'FAMILIAR') {
            return response()->json([
                'message' => 'El socio no cuenta con Plan Familiar',
            ], 403);
        }



        $existe = RegistrosLudoteca::where('id_menor', $request->id_miembro)
            ->where('hora_egreso', null)
            ->first();
        if ($existe != null) {
            return response()->json([
                'message' => 'El menor ya se encuentra registrado',
            ]);
        }

        $familiar = MiembrosFamiliares::where('id_miembro', $request->id_miembro)
            ->where('socio_id', $request->id_socio)
            ->first();
        if ($familiar == null) {
            return response()->json([
                'message' => 'Familiar no encontrado',
            ]);
        }
        $edad = Carbon::parse($familiar->fecha_nacimiento)->age;
        if ($edad < 3 || $edad > 6) {
            return response()->json([
                'message' => 'El menor no cumple con el rango de edad permitido'
            ], 400);
        }
        $registro = RegistrosLudoteca::create([
            'id_menor' => $request->id_miembro,
            'estatus_cuenta' => 'COMPLETADA_A_TIEMPO',
            'hora_ingreso' => now(),
            'id_adulto_ingreso' => $request->id_socio,
            'hora_egreso' => null,
            'id_adulto_egreso' => null,
            'id_instructor_ingreso' => $request->id_instructor,
        ]);
        RegistroLudotecaMongo::insert([
            'tutor_id' => $request->id_socio,
            'menor_id' => $request->id_miembro,
            'tipo_evento' => 'ludoteca_in',
            'timestamp' => now('America/Mexico_City'),
            'metadata' => [
                'id_registro' => $registro->id_registro,

            ]
        ]);

        return response()->json([
            'message' => 'Ingreso registrado correctamente',
            'registro' => $registro->id_menor,
        ]);
    }






}