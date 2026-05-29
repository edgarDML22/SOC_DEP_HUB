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
            'id_plantilla'                               => 'required|integer|exists:plantillas_programacion,id_plantilla',
            // 'present' (no 'required') permite enviar array vacío al descartar
            'payload.actividades'                        => 'present|array',
            'payload.actividades.*.id_disciplina'        => 'required|integer|exists:disciplinas,id_disciplina',
            'payload.actividades.*.id_espacio'           => 'required|integer|exists:espacios_fisicos,id_espacio',
            'payload.actividades.*.id_instructor'        => 'required|integer|exists:instructores,id_instructor',
            'payload.actividades.*.dia_semana'           => 'required|in:LUNES,MARTES,MIERCOLES,JUEVES,VIERNES,SABADO,DOMINGO',
            'payload.actividades.*.hora_inicio'          => 'required|date_format:H:i',
            'payload.actividades.*.hora_fin'             => 'required|date_format:H:i',
            'payload.actividades.*.cupo_maximo'          => 'nullable|integer|min:1|max:200',
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
