<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocioController extends Controller
{
    /**
     * Buscar socios y familiares para autocompletado en reservaciones u otros flujos.
     * 
     * GET /api/v1/socios/search?query=...
     */
    public function search(Request $request)
    {
        $queryParam = $request->query('query', '');

        // Validación inicial para no hacer búsquedas pesadas vacías
        if (empty($queryParam) || strlen($queryParam) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ], 200);
        }

        // 1. Buscar en socios titulares
        // Se utiliza ILIKE asumiendo la conexión de PostgreSQL configurada en Neon
        $titulares = DB::table('socios_titulares')
            ->select('id_socio as id', 'nombre_completo as nombre', 'numero_accion as numero_socio')
            ->where('nombre_completo', 'ILIKE', "%{$queryParam}%")
            ->limit(10)
            ->get()
            ->map(function ($item) {
                // Formateamos como solicita el frontend
                $item->foto_perfil = null; // Placeholder: Puedes llenarlo si tienes columna como foto_url
                $item->tipo_perfil = 'socio_titular';
                return $item;
            });

        // 2. Buscar en miembros familiares
        $familiares = DB::table('miembros_familiares as mf')
            ->join('socios_titulares as st', 'mf.socio_id', '=', 'st.id_socio')
            ->select('mf.id_miembro as id', 'mf.nombre_completo as nombre', 'st.numero_accion as numero_socio')
            ->where('mf.nombre_completo', 'ILIKE', "%{$queryParam}%")
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->foto_perfil = null; // Placeholder
                $item->tipo_perfil = 'miembro_familiar';
                return $item;
            });

        // Combinamos resultados, ordenamos alfabéticamente y limitamos a 15 sugerencias
        $resultados = $titulares->merge($familiares)
            ->sortBy('nombre')
            ->values()
            ->take(15);

        return response()->json([
            'success' => true,
            'data' => $resultados
        ], 200);
    }
}
