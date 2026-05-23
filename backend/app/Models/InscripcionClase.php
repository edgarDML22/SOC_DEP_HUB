<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InscripcionClase extends Model
{
    protected $table = "inscripciones_clases";
    protected $primaryKey = 'id_inscripcion';
    public $timestamps = false;

    protected $fillable = [
        'id_sesion',
        'id_usuario',
        'tipo_usuario',
        'fecha_transaccion',
        'estatus_inscripcion',
        'bloqueo_temporal',
    ];

    protected $casts = [
        'bloqueo_temporal' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    /**
     * Sesión a la que pertenece esta inscripción.
     * Usar withoutGlobalScopes() cuando se necesite el historial completo
     * (fuera del rango de FuturasActivasScope).
     */
    public function sesion()
    {
        return $this->belongsTo(SesionActiva::class, 'id_sesion', 'id_sesion');
    }
}
