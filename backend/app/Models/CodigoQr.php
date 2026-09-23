<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodigoQr extends Model
{
    protected $table = 'codigos_qr';
    protected $primaryKey = 'id_codigo';

    public $timestamps = true;

    protected $fillable = [
        'codigo',
        'usuario_id',
        'tipo_usuario', // Aquí se guarda 'SOCIO', 'FAMILIAR' o 'EXTERNO'
        'fecha_activacion',
        'fecha_expiracion',
        'estatus',
        'deleted_at'
    ];

    /**
     * Relación polimórfica inversa.
     * El nombre 'usuario' debe coincidir con el prefijo usado en los otros modelos.
     */
    public function usuario()
    {
        return $this->morphTo('usuario', 'tipo_usuario', 'usuario_id');
    }
}