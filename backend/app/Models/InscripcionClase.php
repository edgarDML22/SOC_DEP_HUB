<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use App\Models\MiembrosFamiliares;
use App\Models\PasesDiarios;

class InscripcionClase extends Model
{
    protected $table = "inscripciones_clases";
    protected $primaryKey = 'id_inscripcion';
    public $timestamps = false;

    protected $attributes = [
        'bloqueo_temporal' => false,
    ];

    protected $fillable = [
        'id_sesion',
        'id_usuario',
        'id_miembro_familiar',
        'id_pase_invitado',
        'tipo_usuario',
        'fecha_transaccion',
        'estatus_inscripcion',
        'bloqueo_temporal',
    ];

    // ─── Relaciones ──────────────────────────────────────────────────────────

    public function sesion()
    {
        return $this->belongsTo(SesionActiva::class, 'id_sesion', 'id_sesion');
    }

    /**
     * Socio titular inscrito (solo cuando tipo_usuario = 'socio_titular').
     */
    public function socio()
    {
        return $this->belongsTo(SocioTitular::class, 'id_usuario', 'id_socio');
    }

    /**
     * Miembro familiar inscrito (nullable — solo cuando tipo_usuario = 'miembro_familiar').
     */
    public function miembroFamiliar()
    {
        return $this->belongsTo(MiembrosFamiliares::class, 'id_usuario', 'id_miembro');
    }

    /**
     * Pase diario del invitado (nullable — solo cuando tipo_usuario = 'invitado').
     */
    public function paseInvitado()
    {
        return $this->belongsTo(PasesDiarios::class, 'id_pase_invitado', 'id_pase');
    }
}
