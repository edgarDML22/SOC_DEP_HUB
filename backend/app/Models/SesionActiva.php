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
        'id_instructor_sustituto',
        'lista_asistencia_enviada',
    ];

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    public function actividadPlantilla()
    {
        return $this->belongsTo(ActividadPlantilla::class, 'id_actividad_plantilla', 'id_actividad_plantilla');
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
     *
     * Requiere que la relación actividadPlantilla esté cargada (eager load)
     * para evitar N+1. Si no está cargada, devuelve false de forma segura.
     *
     * Uso: $sesion->es_cupo_lleno  (acceso como propiedad)
     */
    public function getEsCupoLlenoAttribute(): bool
    {
        $actividad = $this->actividadPlantilla;

        if (!$actividad || $actividad->cupo_maximo === null) {
            return false;
        }

        return $this->cantidad_inscritos >= $actividad->cupo_maximo;
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
