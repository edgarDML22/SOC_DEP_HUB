<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class DespublicacionBloqueadaException extends Exception
{
    public function __construct(
        private readonly int $sesionesConInscritos,
        private readonly int $sesionesCompletadas,
    ) {
        parent::__construct('No se puede despublicar: la programación ya tiene actividad registrada.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'meta' => [
                'sesiones_con_inscritos' => $this->sesionesConInscritos,
                'sesiones_completadas'   => $this->sesionesCompletadas,
                'motivo'                 => $this->resolverMotivo(),
            ],
        ], 422);
    }

    private function resolverMotivo(): string
    {
        $partes = [];

        if ($this->sesionesConInscritos > 0) {
            $partes[] = "{$this->sesionesConInscritos} sesión(es) con inscripciones activas";
        }

        if ($this->sesionesCompletadas > 0) {
            $partes[] = "{$this->sesionesCompletadas} sesión(es) marcada(s) como COMPLETADA";
        }

        return implode(' y ', $partes) . '.';
    }
}
