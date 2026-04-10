<?php

namespace App\Http\Controllers;

use App\Models\RegistrosLudoteca;
use Illuminate\Http\Request;
use App\Models\SocioTitular;
use App\Models\MiembrosFamiliares;
use App\Models\MongoDB\RegistroLudotecaMongo;
class LudotecaStatusController extends Controller
{
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id_registro' => 'required|exists:registros_ludoteca,id_registro',
            'correo_electronico' => 'required|email',
            'check_in' => 'required|in:in,out',
        ]);
        $id_adulto = SocioTitular::where('correo_electronico', $request->correo_electronico)
            ->value('id_socio');
        if ($id_adulto == null) {
            return response()->json([
                'message' => 'Correo electronico no encontrado',
            ]);
        }
        $status = RegistrosLudoteca::where('id_registro', $request->id_registro)->value('estatus_visita');

        if ($request->check_in == 'in') {
            $id_menor = RegistrosLudoteca::where('id_registro', $request->id_registro)->value('id_menor');

            if ($status == 'ACTIVA') {
                return response()->json([
                    'message' => 'El menor ya se encuentra dentro de la ludoteca',
                ]);
            }
            $familiar = MiembrosFamiliares::where('id_miembro', $id_menor)
                ->where('socio_id', $id_adulto)
                ->first();
            if ($familiar == null) {
                return response()->json([
                    'message' => 'Familiar no encontrado',
                ]);
            }
            $registro = RegistrosLudoteca::where('id_registro', $request->id_registro)->update([
                'estatus_visita' => 'ACTIVA',
                'hora_ingreso' => now(),
                'id_adulto_ingreso' => $id_adulto,
                'hora_egreso' => null,
                'id_adulto_egreso' => null,
            ]);
            RegistroLudotecaMongo::insert([
                'tutor_id' => $id_adulto,
                'menor_id' => $id_menor,
                'tipo_evento' => 'ludoteca_in',
                'timestamp' => now('America/Mexico_City'),
                'metadata' => [
                    'id_registro' => $request->id_registro
                ]
            ]);
            return response()->json([
                'message' => 'Ingreso registrado correctamente',
                'registro' => $registro
            ]);
        }
        if ($status != 'ACTIVA') {
            return response()->json([
                'message' => 'El menor ya no se encuentra dentro de la ludoteca',
            ]);
        }

        $estatus = 'COMPLETADA_A_TIEMPO';
        $time = now();
        $limite = RegistrosLudoteca::where('id_registro', $request->id_registro)->value('hora_limite');
        if ($time > $limite) {
            $estatus = 'COMPLETADA_CON_RETRASO';
        }



        $registro = RegistrosLudoteca::where('id_registro', $request->id_registro)->update([
            'estatus_visita' => $estatus,
            'hora_egreso' => now(),
            'id_adulto_egreso' => $id_adulto,
        ]);
        RegistroLudotecaMongo::insert([
            'tutor_id' => $id_adulto,
            'menor_id' => RegistrosLudoteca::where('id_registro', $request->id_registro)->value('id_menor'),
            'tipo_evento' => 'ludoteca_out',
            'timestamp' => now('America/Mexico_City'),
            'metadata' => [
                'id_registro' => $request->id_registro,
                'estatus_final' => $estatus
            ]
        ]);


        return response()->json([
            'message' => 'Salida registrada correctamente',
            'registro' => $registro
        ]);
    }
}