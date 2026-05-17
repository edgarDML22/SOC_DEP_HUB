<?php

namespace App\Models;

use App\Models\Scopes\FuturasActivasScope;
use Illuminate\Database\Eloquent\Model;

class SesionActiva extends Model
{
    protected $table = "sesiones_activas";
    protected $primaryKey = 'id_sesion';
    public $timestamps = false;

    // Para reportes históricos o Jobs de background: SesionActiva::withoutGlobalScopes()->...
    protected static function booted(): void
    {
        static::addGlobalScope(new FuturasActivasScope());
    }

    protected $fillable = [
        'id_sesion',
        'id_actividad_plantilla',
        'id_espacio',
        'fecha_sesion',
        'id_instructor_sustituto',
    ];

    public function actividadPlantilla()
    {
        return $this->belongsTo(ActividadPlantilla::class, 'id_actividad_plantilla', 'id_actividad_plantilla');
    }

    public function inscripcionesClase()
    {
        // Clase, FK, PK
        return $this->hasMany(InscripcionClase::class, 'id_sesion', 'id_sesion');
    }
}
