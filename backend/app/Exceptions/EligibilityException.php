<?php

namespace App\Exceptions;

use Exception;

class EligibilityException extends Exception
{
    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'motivo' => $this->getMessage()
        ], 422);
    }
}
