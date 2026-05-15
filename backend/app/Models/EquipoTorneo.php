<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CategoriaTorneo;
use App\Models\ParticipantesTorneo;
use App\Models\Torneo;

class EquipoTorneo extends Model
{
    protected $table = "equipos_torneo";
    protected $primaryKey = 'id_equipo_torneo';
    public $timestamps = false;
    protected $fillable = [
        'id_torneo',
        'nombre_equipo',
        'id_categoria',
        'estatus_equipo',

    ];
    public function participantes()
    {
        return $this->hasMany(ParticipantesTorneo::class, 'id_equipo');
    }
    public function torneo()
    {
        return $this->belongsTo(Torneo::class, 'id_torneo');
    }
    public function categoria()
    {
        return $this->belongsTo(CategoriaTorneo::class, 'id_categoria');
    }
}