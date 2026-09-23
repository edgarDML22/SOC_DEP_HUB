<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Torneo extends Model
{
    protected $table = "torneos";
    protected $primaryKey = 'id_torneo';
    protected $casts = [
        'pool_arbitros' => 'array'
    ];
    public $timestamps = false;
    protected $fillable = [
        'id_disciplina',
        'fecha_inicio',
        'fecha_fin',
        'cupo_maximo',
        'tipo_acceso',
        'nombre_torneo',
        'formato_competencia',
        'descripcion',
        'estatus_torneo',
        'id_categoria',
        'cupo_minimo',
        'modalidad',
        'genero_requerido',
        'motivo_cancelacion',
        'pool_arbitros'

    ];
    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class, 'id_disciplina');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaTorneo::class, 'id_categoria');
    }
    public function encuentros()
    {
        return $this->hasMany(EncuentrosTorneo::class, 'id_torneo');
    }

    public function participantes()
    {
        return $this->hasMany(ParticipantesTorneo::class, 'id_torneo');
    }

}