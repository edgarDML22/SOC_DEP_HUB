<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantesTorneo extends Model
{
    protected $table = "participantes_torneo";
    protected $primaryKey = 'id_participante_torneo';
    public $timestamps = false;
    protected $fillable = [
        'id_torneo',
        'id_categoria',
        'tipo_entidad',
        'referencia_id',
        'participante_type',
        'participante_id',
        'id_interno',
        'ranking_declarado',
        'siembra_ranking',
        'fecha_inscripcion',
        'estatus_participacion',
        'estatus_inscripcion',
        'id_equipo',
        'qr_codigo',
        'qr_estatus',
        'id_categoria',
        'tipo_entidad',
        'referencia_id',
        'fecha_inscripcion',
        'estatus_participacion'
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
        return $this->belongsTo(EquiposTorneo::class, 'id_equipo');
    }

    // Accessors for polymorphic relationships
    public function getCorreoAttribute()
    {
        if ($this->participante_type === SocioTitular::class && $this->participante) {
            return $this->participante->correo_electronico;
        } elseif ($this->participante_type === MiembrosFamiliares::class && $this->participante) {
            return $this->participante->correo;
        }
        return null;
    }

    public function getNombreCompletoAttribute()
    {
        if (in_array($this->participante_type, [SocioTitular::class, MiembrosFamiliares::class]) && $this->participante) {
            return $this->participante->nombre_completo;
        }
        return 'Participante Externo';
    }
}