<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocioTitular extends Model
{
    protected $table = 'socios_titulares';
    protected $primaryKey = 'id_socio';
    public $timestamps = false;

    protected $fillable = [
        'fecha_nacimiento',
        'genero',
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
}