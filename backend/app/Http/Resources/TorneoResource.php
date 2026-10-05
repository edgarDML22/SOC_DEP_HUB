<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TorneoResource extends JsonResource
{
    /**
     * Transforma el modelo Torneo en un array JSON.
     *
     * Centraliza la serialización que antes se construía manualmente
     * mediante ->getCollection()->transform(...) en TorneoController@index.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // --- Identificadores ---
            'id'                   => $this->id_torneo,
            'id_torneo'            => $this->id_torneo,

            // --- Atributos básicos ---
            'nombre_torneo'        => $this->nombre_torneo,
            'disciplina'           => $this->whenLoaded('disciplina', fn () => $this->disciplina?->nombre_disciplina),
            'id_disciplina'        => $this->id_disciplina,
            'categoria'            => $this->whenLoaded('categoria', fn () => $this->categoria?->nombre_categoria),
            'tipo_acceso'          => $this->tipo_acceso,
            'formato_competencia'  => $this->formato_competencia,
            'estado'               => $this->estatus_torneo,
            'estatus_torneo'       => $this->estatus_torneo,
            'fecha_inicio'         => $this->fecha_inicio,
            'fecha_fin'            => $this->fecha_fin,
            'cupo_maximo'          => $this->cupo_maximo,
            'cupo_minimo'          => $this->cupo_minimo,
            'modalidad'            => $this->modalidad,
            'genero'               => $this->genero_requerido,
            'descripcion'          => $this->descripcion,
            'motivo_cancelacion'   => $this->motivo_cancelacion,

            // --- Relaciones condicionales ---
            '_encuentros'          => $this->whenLoaded('encuentros', fn () => EncuentroResource::collection($this->encuentros)),
        ];
    }
}
