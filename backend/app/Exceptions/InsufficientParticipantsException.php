<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientParticipantsException extends Exception
{
    protected $message = 'La cantidad de participantes confirmados es menor al cupo mínimo del torneo.';

    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage()
        ], 422);
    }
}
