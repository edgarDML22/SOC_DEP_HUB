<?php

namespace App\Http\Controllers;

use App\Models\MiembrosFamiliares;
use Illuminate\Http\Request;

class MiembrosFamiliaresList extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Si es socio o familiar, obtenemos el ID del titular
        $id_titular = $user->user_id;
        if ($user->rol == 'miembro_familiar') {
            $id_titular = MiembrosFamiliares::where('id_miembro', $user->user_id)->value('socio_id');
        }

        // Si viene un id_socio explícito y es gerente/instructor, lo usamos
        if ($request->id_socio && in_array($user->rol, ['gerente', 'subgerente', 'instructor'])) {
            $id_titular = $request->id_socio;
        }

        $miembros = MiembrosFamiliares::where('socio_id', $id_titular)
            ->whereRaw("DATE_PART('year', AGE(fecha_nacimiento)) BETWEEN 3 AND 6")
            ->select('id_miembro', 'nombre_completo')
            ->get();

        if ($miembros->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron menores en el rango de edad permitido (3-6 años)',
                'data' => []
            ], 200);
        }

        return response()->json($miembros);
    }
}