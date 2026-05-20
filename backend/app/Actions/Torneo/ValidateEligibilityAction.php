<?php

namespace App\Actions\Torneo;

use App\Models\Torneo;
use App\Exceptions\EligibilityException;
use Carbon\Carbon;

class ValidateEligibilityAction
{
    /**
     * Valida la elegibilidad de un participante para un torneo.
     *
     * @param  \App\Models\Torneo  $torneo
     * @param  mixed  $participante (SocioTitular o MiembrosFamiliares)
     * @return void
     * @throws \App\Exceptions\EligibilityException
     */
    public function execute(Torneo $torneo, $participante): void
    {
        $categoria = $torneo->categoria;
        if (!$categoria) {
            return; // Si el torneo no tiene categoría asociada, no se aplican restricciones
        }

        // 1. Validación de Edad
        if ($participante->fecha_nacimiento) {
            $edad = Carbon::parse($participante->fecha_nacimiento)->diffInYears(Carbon::now());
            
            $min = $categoria->edad_minima;
            $max = $categoria->edad_maxima;

            if ($min !== null && $max !== null) {
                if ($edad < $min || $edad > $max) {
                    throw new EligibilityException(
                        "El participante no cumple el rango de edad: requiere {$min}-{$max}, tiene {$edad}."
                    );
                }
            } elseif ($min !== null && $edad < $min) {
                throw new EligibilityException(
                    "El participante no cumple la edad mínima: requiere al menos {$min}, tiene {$edad}."
                );
            } elseif ($max !== null && $edad > $max) {
                throw new EligibilityException(
                    "El participante no cumple la edad máxima: requiere máximo {$max}, tiene {$edad}."
                );
            }
        }

        // 2. Validación de Género
        if ($categoria->genero_requerido) {
            $generoCat = strtoupper(trim($categoria->genero_requerido));
            $generoPart = $participante->genero ? strtoupper(trim($participante->genero)) : null;

            if ($generoCat !== 'MIXTO' && $generoPart) {
                $isMaleCat = ($generoCat === 'M' || $generoCat === 'VARONIL' || $generoCat === 'MASCULINO');
                $isFemaleCat = ($generoCat === 'F' || $generoCat === 'FEMENIL' || $generoCat === 'FEMENINO');

                $isMalePart = ($generoPart === 'M' || $generoPart === 'VARONIL' || $generoPart === 'MASCULINO');
                $isFemalePart = ($generoPart === 'F' || $generoPart === 'FEMENIL' || $generoPart === 'FEMENINO');

                if (($isMaleCat && !$isMalePart) || ($isFemaleCat && !$isFemalePart)) {
                    $reqText = $isMaleCat ? 'Varonil' : 'Femenil';
                    $partText = $isMalePart ? 'Varonil' : ($isFemalePart ? 'Femenil' : $participante->genero);
                    throw new EligibilityException(
                        "El género del participante ({$partText}) no coincide con el requerido por la categoría ({$reqText})."
                    );
                }
            }
        }
    }
}
