<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TournamentFullException extends Exception
{
    protected $message = 'Cupo máximo del torneo alcanzado.';

    public function render($request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage()
        ], 409);
    }
}
