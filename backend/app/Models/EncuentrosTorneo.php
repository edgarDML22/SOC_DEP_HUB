<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuentrosTorneo extends Model
{
    protected $table = "encuentros_torneo";
    protected $primaryKey = 'id_encuentro';
    public $timestamps = false;
    protected $fillable = [
        'id_encuentro',
        'fase_bracket',
        'id_arbitro_asignado',
        'id_espacio',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'competidor_1_id',
        'competidor_2_id',
        'estatus_encuentro',
        'id_ganador',
        'id_torneo',
        'competidor_1_type',
        'competidor_2_type',
        'resultado_comp1',
        'resultado_comp2',
        'es_bye',
        'numero_encuentro',
        'id_espacio',
        'fase_bracket'
    ];

    public function competidor1()
    {
        return $this->morphTo(
            __FUNCTION__,
            'competidor_1_type',
            'competidor_1_id'
        );
    }

    public function competidor2()
    {
        return $this->morphTo(
            __FUNCTION__,
            'competidor_2_type',
            'competidor_2_id'
        );
    }

    public function espacioFisico()
    {
        return $this->belongsTo(EspacioFisico::class, 'id_espacio', 'id_espacio');
    }

    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo', 'id_torneo');
    }
}