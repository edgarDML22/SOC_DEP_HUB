<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientSlotsException extends Exception
{
    protected $message = 'No hay suficientes horarios disponibles para programar todos los encuentros del torneo.';

    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage()
        ], 422);
    }
}
