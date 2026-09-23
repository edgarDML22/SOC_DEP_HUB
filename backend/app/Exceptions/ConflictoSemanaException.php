<?php

namespace App\Exceptions;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;

class ConflictoSemanaException extends Exception
{
    private Carbon $lunes;
    private Carbon $domingo;
    private int    $totalSesiones;

    public function __construct(Carbon $lunes, Carbon $domingo, int $totalSesiones)
    {
        $this->lunes         = $lunes;
        $this->domingo       = $domingo;
        $this->totalSesiones = $totalSesiones;

        parent::__construct(
            "Ya existen {$totalSesiones} sesiones publicadas para la semana "
            . "{$lunes->toDateString()} – {$domingo->toDateString()}."
        );
    }

    /**
     * Renderiza la respuesta HTTP 409 para el cliente.
     * Laravel llama este método automáticamente cuando la excepción
     * burbujea hasta el Handler sin ser capturada en el controlador.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error'   => 'CONFLICTO_SEMANA',
            'meta'    => [
                'semana_inicio'   => $this->lunes->toDateString(),
                'semana_fin'      => $this->domingo->toDateString(),
                'sesiones_existentes' => $this->totalSesiones,
            ],
        ], 409);
    }
}
