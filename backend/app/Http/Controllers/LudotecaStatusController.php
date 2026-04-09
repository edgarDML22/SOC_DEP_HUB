<?php

namespace App\Http\Controllers;

use App\Models\RegistrosLudoteca;
use Illuminate\Http\Request;
use App\Models\SocioTitular;


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
        if ($request->check_in == 'in') {
            $registro = RegistrosLudoteca::where('id_registro', $request->id_registro)->update([
                'estatus_visita' => 'ACTIVA',
                'hora_ingreso' => now(),
                'id_adulto_ingreso' => $id_adulto,
                'hora_egreso' => null,
                'id_adulto_egreso' => null,
            ]);
            return response()->json([
                'message' => 'Ingreso registrado correctamente',
                'registro' => $registro
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


        return response()->json([
            'message' => 'Correo electronico actualizado correctamente',
            'registro' => $registro
        ]);
    }
}