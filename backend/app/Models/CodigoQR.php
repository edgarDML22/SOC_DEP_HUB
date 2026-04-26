<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodigoQr extends Model
{
    protected $table = 'codigos_qr';
    protected $primaryKey = 'id_codigo';
    
    public $timestamps = true; 
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'codigo',
        'usuario_id',
        'tipo_usuario_qr', // Enum: 'SOCIO', 'FAMILIAR', 'EXTERNO'
        'fecha_activacion',
        'fecha_expiracion',
        'estatus_codigo_qr' // Enum: 'ACTIVO', 'USADO', 'EXPIRADO', 'INACTIVO'
    ];

    public function usuario()
    {
        return $this->morphTo(__FUNCTION__, 'tipo_usuario_qr', 'usuario_id');
    }
}