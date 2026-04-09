<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgendaEspacioRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Para filtrar por fechas
            'id_espacio' => ['required','integer','min:1'],
            'date' => ['nullable', 'date', 'date_format:Y-m-d']
        ];
    }
}
