<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            "fecha" => ['required','date','after_or_equal:today'],
            "hora_inicio" => ['required','date_format:H:i'],
            "hora_fin" => ['required','date_format:H:i','after:hora_inicio'],
            "id_espacio" => ['required','integer','exists:espacios_fisicos,id_espacio']

            //
        ];
    }

}
