<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantesTorneo extends Model
{
    protected $table = "participantes_torneo";
    protected $primaryKey = 'id_participante_torneo';
    public $timestamps = false;
    protected $fillable = [
        'id_categoria',
        'tipo_entidad',
        'referencia_id',
        'siembra_ranking',
        'fecha_inscripcion',
        'estatus_participacion',
        'id_torneo',
        'participante_type',
        'participante_id',
        'id_interno',
        'ranking_declarado',
        'id_equipo',
        'estatus_inscripcion'
    ];
    public function participante()
    {
        return $this->morphTo(
            __FUNCTION__,
            'participante_type',
            'participante_id'
        );
    }
    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo');
    }

    public function equipo()
    {
        return $this->belongsTo(EquipoTorneo::class, 'id_equipo');
    }
}