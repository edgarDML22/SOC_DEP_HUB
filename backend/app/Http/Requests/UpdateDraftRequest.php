<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payload'                                    => 'required|array',
            'payload.nombre_plantilla'                   => 'sometimes|string|max:100',
            'payload.fecha_inicio'                       => 'sometimes|date_format:Y-m-d',
            'payload.fecha_fin'                          => 'sometimes|date_format:Y-m-d|after_or_equal:payload.fecha_inicio',
            'payload.actividades'                        => 'sometimes|array',
            'payload.actividades.*.id_disciplina'        => 'required_with:payload.actividades|integer|exists:disciplinas,id_disciplina',
            'payload.actividades.*.id_espacio'           => 'required_with:payload.actividades|integer|exists:espacios_fisicos,id_espacio',
            'payload.actividades.*.id_instructor'        => 'required_with:payload.actividades|integer|exists:instructores,id_instructor',
            'payload.actividades.*.dia_semana'           => 'required_with:payload.actividades|in:LUNES,MARTES,MIERCOLES,JUEVES,VIERNES,SABADO,DOMINGO',
            'payload.actividades.*.hora_inicio'          => 'required_with:payload.actividades|date_format:H:i',
            'payload.actividades.*.hora_fin'             => 'required_with:payload.actividades|date_format:H:i',
            'payload.actividades.*.cupo_maximo'          => 'required_with:payload.actividades|integer|min:1|max:200',
            'payload.actividades.*.requiere_inscripcion' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'payload.actividades.*.id_disciplina.exists' => 'Una disciplina seleccionada no existe.',
            'payload.actividades.*.id_espacio.exists'    => 'Un espacio seleccionado no existe.',
            'payload.actividades.*.id_instructor.exists' => 'Un instructor seleccionado no existe.',
            'payload.actividades.*.dia_semana.in'        => 'El día debe ser LUNES, MARTES, MIERCOLES, JUEVES, VIERNES, SABADO o DOMINGO.',
        ];
    }
}
