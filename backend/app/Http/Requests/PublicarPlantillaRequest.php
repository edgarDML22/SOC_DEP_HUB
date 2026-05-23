<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PublicarPlantillaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semana_inicio' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'semana_inicio.required'    => 'El campo semana_inicio es obligatorio.',
            'semana_inicio.date_format' => 'El campo semana_inicio debe tener el formato YYYY-MM-DD.',
        ];
    }

    /**
     * Hook post-validación: verifica que semana_inicio sea estrictamente un lunes.
     * Se ejecuta solo si las reglas básicas pasaron, evitando parseos fallidos.
     */
    protected function passedValidation(): void
    {
        $fecha = Carbon::createFromFormat('Y-m-d', $this->input('semana_inicio'));

        // dayOfWeekIso: 1 = lunes … 7 = domingo
        if ($fecha->dayOfWeekIso !== 1) {
            throw ValidationException::withMessages([
                'semana_inicio' => [
                    'La fecha de inicio de semana debe ser un lunes. '
                    . "Se recibió: {$fecha->locale('es')->dayName} ({$fecha->toDateString()}).",
                ],
            ]);
        }
    }
}
