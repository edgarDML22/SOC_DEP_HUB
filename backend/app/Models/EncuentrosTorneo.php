<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuentrosTorneo extends Model
{
    protected $table = "encuentros_torneo";
    protected $primaryKey = 'id_encuentro';
    public $timestamps = false;
    protected $fillable = [
        'id_encuentro',
        'id_arbitro_asignado',
        'fecha_hora_inicio',
        'fecha_hora_fin',
    ];
}