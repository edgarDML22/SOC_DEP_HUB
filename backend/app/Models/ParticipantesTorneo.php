<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParticipantesTorneo extends Model
{
    protected $table = "participantes_torneo";
    protected $primaryKey = 'id_participante_torneo';
    public $timestamps = false;

    // Append accessor so it appears in JSON responses
    protected $appends = ['nombre_completo'];

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
        return $this->belongsTo(EquiposTorneo::class, 'id_equipo', 'id_equipo_torneo');
    }

    /**
     * Relación para cuando este participante ES el capitán/creador de un equipo.
     * Permite eager loading: competidor1.capitanDeEquipo
     */
    public function capitanDeEquipo()
    {
        return $this->hasOne(EquiposTorneo::class, 'id_participante_torneo', 'id_participante_torneo');
    }

    // Accessors for polymorphic relationships
    public function getCorreoAttribute()
    {
        $id   = $this->participante_id;
        $type = $this->participante_type;
        if ($id && in_array($type, ['SOCIO', SocioTitular::class])) {
            return SocioTitular::find($id)?->correo_electronico;
        } elseif ($id && in_array($type, ['FAMILIAR', MiembrosFamiliares::class])) {
            return MiembrosFamiliares::find($id)?->correo;
        }
        return null;
    }

    public function getNombreCompletoAttribute()
    {
        // ── 1. Capitán del equipo: usa relación eager-loaded si está disponible,
        //       de lo contrario hace la query. Esto evita N+1 cuando el controlador
        //       carga 'competidor1.capitanDeEquipo' por eager loading.
        $equipoCapitan = $this->relationLoaded('capitanDeEquipo')
            ? $this->capitanDeEquipo
            : EquiposTorneo::where('id_participante_torneo', $this->id_participante_torneo)->first();

        if ($equipoCapitan && $equipoCapitan->nombre_equipo) {
            return $equipoCapitan->nombre_equipo;
        }

        // ── 2. Miembro de equipo: usa relación eager-loaded si está disponible ──
        if (!empty($this->id_equipo)) {
            $equipo = $this->relationLoaded('equipo')
                ? $this->equipo
                : EquiposTorneo::where('id_equipo_torneo', $this->id_equipo)->first();

            if ($equipo && $equipo->nombre_equipo) {
                return $equipo->nombre_equipo;
            }
        }

        $id   = $this->participante_id;
        $type = $this->participante_type;

        // Socio titular: buscar directo en socios_titulares
        if ($id && in_array($type, ['SOCIO', SocioTitular::class])) {
            return SocioTitular::find($id)?->nombre_completo;
        }

        // Miembro familiar: buscar directo en miembros_familiares
        if ($id && in_array($type, ['FAMILIAR', MiembrosFamiliares::class])) {
            return MiembrosFamiliares::find($id)?->nombre_completo;
        }

        // Competidor externo: buscar en pre_registros_torneo por referencia_id
        if ($this->tipo_entidad === 'COMPETIDOR_EXTERNO') {
            if ($this->referencia_id && $this->referencia_id > 0) {
                $pre = PreRegistroTorneo::find($this->referencia_id);
                if ($pre && isset($pre->datos_participante['nombre_completo'])) {
                    return $pre->datos_participante['nombre_completo'];
                }
            }

            // Fallback heurístico para registros antiguos con referencia_id = 0
            $pre = PreRegistroTorneo::where('id_torneo', $this->id_torneo)
                ->where('estatus', 'APROBADO')
                ->where('tipo', 'INDIVIDUAL')
                ->orderBy('id')
                ->skip($this->id_interno)
                ->first();
            if ($pre && isset($pre->datos_participante['nombre_completo'])) {
                return $pre->datos_participante['nombre_completo'];
            }
        }

        return 'Participante Externo';
    }
}