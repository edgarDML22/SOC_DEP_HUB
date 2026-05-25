<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SesionActiva;
use App\Models\SocioTitular;

class InscripcionClase extends Model
{
    protected $table = "inscripciones_clases";
    protected $primaryKey = 'id_inscripcion';
    public $timestamps = false;
    //

    protected $fillable = [
        'id_sesion',
        'id_usuario',
        'tipo_usuario',
        'fecha_transaccion',
        'estatus_inscripcion',
        'bloqueo_temporal',
    ];

    public function sesion()
    {
        return $this->belongsTo(SesionActiva::class, 'id_sesion', 'id_sesion');
    }

    public function socio()
    {
        return $this->belongsTo(SocioTitular::class, 'id_usuario', 'id_socio');
    }
}
