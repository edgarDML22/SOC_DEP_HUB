<?php

namespace App\Models;

use App\Models\Scopes\FuturasActivasScope;
use Illuminate\Database\Eloquent\Model;

class SesionActiva extends Model
{
    protected $table = "sesiones_activas";
    protected $primaryKey = 'id_sesion';
    public $timestamps = false;

    // Para reportes históricos o comandos de automatización: SesionActiva::withoutGlobalScopes()->...
    protected static function booted(): void
    {
        static::addGlobalScope(new FuturasActivasScope());
    }

    protected $fillable = [
        'id_sesion',
        'id_actividad_plantilla',
        'id_espacio',
        'fecha_sesion',
        'estatus_sesion',
        'cantidad_inscritos',
        'fecha_publicacion',
        'lista_asistencia_enviada',
        // Snapshot columns — se copian desde actividades_plantilla al crear la sesión
        'id_disciplina',
        'id_instructor',
        'cupo_maximo',
        'hora_inicio',
        'hora_fin',
        'requiere_inscripcion',
        'dia_semana',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    public function actividadPlantilla()
    {
        return $this->belongsTo(ActividadPlantilla::class, 'id_actividad_plantilla', 'id_actividad_plantilla');
    }

    public function disciplina()
    {
        return $this->belongsTo(\App\Models\Disciplina::class, 'id_disciplina', 'id_disciplina');
    }

    public function espacio()
    {
        return $this->belongsTo(\App\Models\EspacioFisico::class, 'id_espacio', 'id_espacio');
    }

    public function instructorPrincipal()
    {
        return $this->belongsTo(\App\Models\Instructor::class, 'id_instructor', 'id_instructor');
    }

    public function inscripcionesClase()
    {
        return $this->hasMany(InscripcionClase::class, 'id_sesion', 'id_sesion');
    }

    // -------------------------------------------------------------------------
    // Atributos dinámicos
    // -------------------------------------------------------------------------

    /**
     * Indica si el cupo de la sesión está lleno.
     * Lee cupo_maximo directamente del snapshot para evitar eager load de actividadPlantilla.
     */
    public function getEsCupoLlenoAttribute(): bool
    {
        if ($this->cupo_maximo === null) {
            return false;
        }

        return $this->cantidad_inscritos >= $this->cupo_maximo;
    }

    /**
     * Indica si la sesión finalizó sin que se enviara la lista de asistencia.
     *
     * Útil para alertas en el dashboard del subgerente.
     */
    public function getAsistenciaPendienteAttribute(): bool
    {
        return $this->estatus_sesion === 'FINALIZADA' && !$this->lista_asistencia_enviada;
    }
}
