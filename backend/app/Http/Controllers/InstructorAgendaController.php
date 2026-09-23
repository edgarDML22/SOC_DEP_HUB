<?php

namespace App\Http\Controllers;

use App\Services\InstructorAgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InstructorAgendaController extends Controller
{
    public function __construct(private InstructorAgendaService $agendaService) {}

    /**
     * GET /v1/instructor/mi-agenda
     *
     * Parámetros opcionales:
     *   desde  Y-m-d  (default: hoy)
     *   hasta  Y-m-d  (default: hoy + 7 días)
     */
    public function miAgenda(Request $request)
    {
        $user = $request->user();

        if ($user->rol !== 'instructor') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Rol inválido.',
            ], 403);
        }

        $tz    = 'America/Mexico_City';
        $hoy   = Carbon::now($tz)->toDateString();
        $desde = $request->query('desde', $hoy);
        $hasta = $request->query('hasta', Carbon::now($tz)->addDays(7)->toDateString());

        $request->validate([
            'desde' => 'nullable|date_format:Y-m-d',
            'hasta' => 'nullable|date_format:Y-m-d|after_or_equal:desde',
        ]);

        $data = $this->agendaService->obtenerAgenda($user->user_id, $desde, $hasta);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
