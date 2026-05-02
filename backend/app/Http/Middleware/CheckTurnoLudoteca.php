<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TurnosLudoteca;
use Carbon\Carbon;

class CheckTurnoLudoteca
{
    public function handle(Request $request, Closure $next)
    {
        $horaActual = Carbon::now()->format('H:i:s');
        $fechaActual = Carbon::now()->format('Y-m-d');

        $turno = TurnosLudoteca::where('id_instructor', $request->id_instructor)
            ->where('fecha', $fechaActual)
            ->where('hora_inicio', '<=', $horaActual)
            ->where('hora_fin', '>=', $horaActual)
            ->first();

        if (!$turno) {
            return response()->json([
                'status' => false,
                'message' => 'Fuera de turno'
            ], 403);
        }

        return $next($request);
    }
}