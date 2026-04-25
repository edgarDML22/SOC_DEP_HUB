<?php

namespace App\Http\Controllers;

use App\Models\RegistrosLudoteca;
use Illuminate\Http\Request;
use App\Models\SocioTitular;
use App\Models\MiembrosFamiliares;
use App\Models\MongoDB\RegistroLudotecaMongo;
class LudotecaStatusController extends Controller
{
    public function checkIn(Request $request)
    {
        //VALIDACION DE CAMPOS REQUERIDOS, es necesario que el front envie el tipo de check, ya sea 'in' o 'out'
        $request->validate([
            'id_socio' => 'required|exists:socios_titulares,id_socio',
            'id_registro' => 'required|exists:registros_ludoteca,id_registro',
            'id_instructor' => 'required|exists:instructores,id_instructor',
        ]);

        //VALIDACION 1: MODALIDAD DE PLAN DEL SOCIO
        $modalidad_plan = SocioTitular::where('id_socio', $request->id_socio)->first();
        if ($modalidad_plan->modalidad_plan != 'FAMILIAR') {
            return response()->json([
                'message' => 'Actualice a plan familiar para usar este servicio',
                'error' => 'PLAN INCORRECTO'
            ], 403);
        }

        //VALIDACION 2: MENOR YA NO SE ENCUENTRA DENTRO DE LA LUDOTECA
        $id_menor = RegistrosLudoteca::where('id_registro', $request->id_registro)
            ->value('id_menor');

        $alreadyActiveToday = RegistrosLudoteca::where('id_menor', $id_menor)
            ->whereDate('hora_ingreso', today())
            ->where('estatus_visita', 'ACTIVA')
            ->exists();

        if ($alreadyActiveToday) {
            return response()->json([
                'message' => 'El menor ya tiene una estancia activa hoy'
            ], 409);
        }

        //Si el check_in es IN
        $id_menor = RegistrosLudoteca::where('id_registro', $request->id_registro)->value('id_menor');
        $familiar = MiembrosFamiliares::where('id_miembro', $id_menor)
            ->where('socio_id', $request->id_socio)
            ->first();
        if ($familiar == null) {
            return response()->json([
                'message' => 'Familiar no encontrado',
            ]);
        }
        $registro = RegistrosLudoteca::where('id_registro', $request->id_registro)->update([
            'estatus_visita' => 'ACTIVA',
            'hora_ingreso' => now(),
            'id_adulto_ingreso' => $request->id_socio,
            'hora_egreso' => null,
            'id_adulto_egreso' => null,
            'id_instructor_ingreso' => $request->id_instructor,
        ]);

        return response()->json([
            'message' => 'Ingreso registrado correctamente',
            'registro' => $registro
        ]);

    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'id_socio' => 'required|exists:socios_titulares,id_socio',
            'id_instructor' => 'required|exists:instructores,id_instructor',
        ]);


        //Si el check_in es OUT


        $estatus = 'COMPLETADA_A_TIEMPO';
        $time = now();
        $limite = RegistrosLudoteca::where('id_registro', $id)->value('hora_limite');
        if ($time > $limite) {
            $estatus = 'COMPLETADA_CON_RETRASO';
        }
        $registro = RegistrosLudoteca::where('id_registro', $id)->update([
            'estatus_visita' => $estatus,
            'hora_egreso' => now(),
            'id_adulto_egreso' => $request->id_socio,
            'id_instructor_egreso' => $request->id_instructor,
        ]);


        //Mongo
        RegistroLudotecaMongo::insert([
            'tutor_id' => $request->id_socio,
            'menor_id' => RegistrosLudoteca::where('id_registro', $id)->value('id_menor'),
            'hora_ingreso' => RegistrosLudoteca::where('id_registro', $id)->value('hora_ingreso'),
            'hora_egreso' => now('America/Mexico_City'),
            'instructor_ingreso' => RegistrosLudoteca::where('id_registro', $id)->value('id_instructor_ingreso'),
            'instructor_egreso' => $request->id_instructor,
            'metadata' => [
                'id_registro' => $id,
                'estatus_final' => $estatus
            ]
        ]);
        return response()->json([
            'message' => 'Salida registrada correctamente'
        ]);
    }
    /* ISRA MIRA ESTO */
    /* ESTE REGRESA LOS REGISTROS DE HOY, EN LA TASK SALE CON NOMBRE DIFERENTE*/
    public function getChildrenStatus(Request $request)
    {
        $request->validate([
            'id_socio' => 'required|exists:socios_titulares,id_socio',
        ]);
        //REGISTROS DE HOYs
        return RegistrosLudoteca::whereDate('hora_ingreso', today())->where('id_adulto_ingreso', $request->id_socio)->get();
    }
}