<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarDisponibilidadRequest extends FormRequest
{

    public function authorize(): bool
    {
        //Pendiente de agregar
        return true;
    }


    public function rules(): array
    {
        return [
            // Para filtrar por fechas
            'date' => ['nullable', 'date', 'date_format:Y-m-d'],
        // Para filtrar por categoría ['MENTE_CUERPO', 'DEPORTES_RAQUETA', 'DEPORTES_EQUIPO', 
        // 'ACONDICIONAMIENTO_FISICO', 'ACONDICIONAMIENTO_FISICO', 'GIMNASIA', 'ACUATICO']
            'category' => ['nullable', 'in:MENTE_CUERPO,DEPORTES_RAQUETA,DEPORTES_EQUIPO,ACONDICIONAMIENTO_FISICO,GIMNASIA,ACUATICO'],
            'espacio_type' => ['required', 'in:RESERVA_ON_DEMAND,CLASE_PROGRAMADA'],
        ];
    }

    // public function messages(): array
    // {
    //     // Mensajes de error
    //     return [
    //         '' => ''
    //     ];
    // }
}
