<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasesDiarios extends Model
{
    use HasFactory;

    protected $table = 'pases_diarios';
    protected $primaryKey = 'id_pase';
    public $timestamps = false;


    protected $fillable = [

        'invitado_id',
        'estatus_acceso',
        'fecha_activacion',
        'fecha_expiracion',


    ];
}
