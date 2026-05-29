<?php

namespace App\Actions;

use App\Models\CategoriaTorneo;
use App\Models\SocioTitular;
use Illuminate\Validation\ValidationException;

/**
 * ValidarElegibilidadAction
 *
 * Verifica que un socio cumple los requisitos de elegibilidad para inscribirse
 * en un torneo con una categoría determinada.
 *
 * Checks:
 *  1. El socio existe y está AL_CORRIENTE (sin suspensión de cuenta).
 *  2. El socio no tiene penalización activa (estatus_penalizacion != SIN_PENALIZACION).
 *  3. La edad del socio está dentro del rango [edad_minima, edad_maxima] de la categoría.
 *  4. El género del socio es compatible con el genero_requerido del torneo.
 *
 * Uso:
 *   app(ValidarElegibilidadAction::class)->execute($socioId, $idCategoria);
 *   // lanza ValidationException con { message: "El compañero no cumple..." } si no pasa.
 */
class ValidarElegibilidadAction
{
    /**
     * @throws ValidationException
     */
    public function execute(int $socioId, int $idCategoria): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Verificar que el socio existe
        |--------------------------------------------------------------------------
        */
        $socio = SocioTitular::find($socioId);

        if (!$socio) {
            throw ValidationException::withMessages([
                'companero' => ['El compañero no existe en el sistema.'],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Verificar estatus de cuenta activa
        |--------------------------------------------------------------------------
        */
        if ($socio->estatus_cuenta !== 'AL_CORRIENTE') {
            throw ValidationException::withMessages([
                'companero' => [
                    "El compañero no cumple la categoría del torneo: su cuenta está en estatus '{$socio->estatus_cuenta}'."
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Verificar que no tenga penalización activa
        |--------------------------------------------------------------------------
        */
        if ($socio->estatus_penalizacion !== 'SIN_PENALIZACION') {
            throw ValidationException::withMessages([
                'companero' => [
                    "El compañero no cumple la categoría del torneo: tiene una penalización activa ({$socio->estatus_penalizacion})."
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Verificar rango de edad de la categoría
        |--------------------------------------------------------------------------
        */
        $categoria = CategoriaTorneo::find($idCategoria);

        if ($categoria) {
            $edad = $socio->fecha_nacimiento
                ? $socio->fecha_nacimiento->age
                : null;

            if (!is_null($edad)) {
                if (!is_null($categoria->edad_minima) && $edad < $categoria->edad_minima) {
                    throw ValidationException::withMessages([
                        'companero' => [
                            "El compañero no cumple la categoría del torneo: edad insuficiente (mínimo {$categoria->edad_minima} años, tiene {$edad})."
                        ],
                    ]);
                }

                if (!is_null($categoria->edad_maxima) && $edad > $categoria->edad_maxima) {
                    throw ValidationException::withMessages([
                        'companero' => [
                            "El compañero no cumple la categoría del torneo: supera la edad máxima ({$categoria->edad_maxima} años, tiene {$edad})."
                        ],
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Verificar género requerido (M, F — null o MIXTO = sin restricción)
            |--------------------------------------------------------------------------
            */
            if (
                !is_null($categoria->genero_requerido) &&
                !in_array($categoria->genero_requerido, ['MIXTO', '']) &&
                $socio->genero !== $categoria->genero_requerido
            ) {
                throw ValidationException::withMessages([
                    'companero' => [
                        "El compañero no cumple la categoría del torneo: el torneo requiere género '{$categoria->genero_requerido}'."
                    ],
                ]);
            }
        }
    }
}
