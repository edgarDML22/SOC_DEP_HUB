<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquiposTorneo extends Model
{
    use HasFactory;

    protected $table = 'equipos_torneo';

    protected $primaryKey = 'id_equipo_torneo';

    public $timestamps = false;

    protected $fillable = [

        // Relación participante capitán
        'id_participante_torneo',

        // Categoría
        'id_categoria',

        // Tipo entidad
        'tipo_entidad',

        // Referencia
        'referencia_id',

        // Interno
        'id_interno',

        // Ranking
        'siembra_ranking',

        // Posición
        'posicion_actual',

        // Puntos
        'puntos_torneo',

        // Estado participación
        'estatus_participacion',

        // FK torneo
        'id_torneo',

        // Nombre equipo
        'nombre_equipo',

        // Estado equipo
        'estatus_equipo',
    ];

    protected $casts = [

        'puntos_torneo' => 'integer',

        'siembra_ranking' => 'integer',

        'posicion_actual' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    // Torneo
    public function torneo()
    {
        return $this->belongsTo(
            Torneo::class,
            'id_torneo',
            'id_torneo'
        );
    }

    // Capitán / participante principal
    public function participanteCapitan()
    {
        return $this->belongsTo(
            ParticipantesTorneo::class,
            'id_participante_torneo',
            'id_participante_torneo'
        );
    }

    // Participantes equipo
    public function participantes()
    {
        return $this->hasMany(
            ParticipantesTorneo::class,
            'id_equipo',
            'id_equipo_torneo'
        );
    }
}