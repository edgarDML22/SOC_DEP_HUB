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
        'competidor_1_id',
        'competidor_2_id'
    ];

    public function competidor1()
    {
        return $this->belongsTo(MiembrosFamiliares::class, 'competidor_1_id');
    }

    public function competidor2()
    {
        return $this->belongsTo(MiembrosFamiliares::class, 'competidor_2_id');
    }

}