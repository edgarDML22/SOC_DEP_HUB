<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocioTitular extends Model
{
    protected $table = 'socios_titulares';
    protected $primaryKey = 'id_socio';
    public $timestamps = false;

    protected $fillable = [
        'nombre_completo',
        'correo_electronico',
        'fecha_nacimiento',
        'genero',
        'tipo_socio',
        'modalidad_plan',
        'estatus_cuenta',
        'contador_no_shows',
        'retrasos_ludoteca',
        'fecha_fin_penalizacion',
    ];

    protected $casts = [
        'fecha_fin_penalizacion' => 'datetime',
        'contador_no_shows' => 'integer',
        'retrasos_ludoteca' => 'integer',
    ];

    public function invitados()
    {
        return $this->hasMany(Invitados::class, 'socio_id', 'id_socio');
    }

    public function codigoQrActivo()
    {
        return $this->morphOne(CodigoQr::class, 'usuario', 'tipo_usuario_qr', 'usuario_id')
            ->where('estatus_codigo_qr', 'ACTIVO'); //quitar despues
    }

    public function codigosQr()
    {
        return $this->morphMany(CodigoQr::class, 'usuario', 'tipo_usuario_qr', 'usuario_id');
    }

    public function miembrosFamiliares()
    {
        return $this->hasMany(MiembrosFamiliares::class, 'socio_id', 'id_socio');
    }
}