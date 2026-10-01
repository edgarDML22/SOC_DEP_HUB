<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EncuentroResource extends JsonResource
{
    /**
     * Transforma el modelo EncuentrosTorneo en un array JSON.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_encuentro'         => $this->id_encuentro,
            'id_torneo'            => $this->id_torneo,
            'fase_bracket'         => $this->fase_bracket ?? $this->fase ?? 'N/A',
            'fase'                 => $this->fase_bracket ?? $this->fase ?? 'N/A',
            'numero_encuentro'     => $this->numero_encuentro,
            'es_bye'               => (bool) $this->es_bye,
            'fecha_hora_inicio'    => $this->fecha_hora_inicio,
            'fecha_hora_fin'       => $this->fecha_hora_fin,
            'id_espacio'           => $this->id_espacio,
            'id_arbitro_asignado'  => $this->id_arbitro_asignado,
            'estatus_encuentro'    => $this->estatus_encuentro,
            'resultado_comp1'      => $this->resultado_comp1,
            'resultado_comp2'      => $this->resultado_comp2,
            'id_ganador'           => $this->id_ganador,
            'competidor1'          => $this->whenLoaded('competidor1', fn () => $this->competidor1),
            'competidor2'          => $this->whenLoaded('competidor2', fn () => $this->competidor2),
        ];
    }
}
