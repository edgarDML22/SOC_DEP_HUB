<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgendaEspacioRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('id') && !$this->has('id_espacio')) {
            $this->merge(['id_espacio' => $this->route('id')]);
        }
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
