<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invitados extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'invitados';
    protected $primaryKey = 'id_invitado';
    public $timestamps = false;

    protected $fillable = [
        'id_invitado',
        'socio_id',
        'nombre_invitado',
        'socio_titulare',
        'codigo_qr',
        'correo',
        'telefono',


    ];

    public function socioTitular()
    {
        return $this->belongsTo(SocioTitular::class, 'id_socio', 'socio_id');
    }
    public function pase()
    {
        return $this->hasOne(PasesDiarios::class, 'invitado_id', 'id_invitado');
    }
}