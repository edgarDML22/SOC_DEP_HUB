<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservacion extends Model
{
    use HasFactory;
    protected $table = "reservaciones_on_demand";
    protected $primaryKey = 'id_reserva';
    public $timestamps = true;

    protected $fillable = [
        'id_socio_titular',
        'id_espacio',
        'fecha_reserva',
        'hora_inicio',
        'hora_fin',
        'estatus_operativo',
        'fecha_expiracion',
    ];

    public function espacioFisico(){
        return $this->hasOne(EspacioFisico::class, 'id_espacio', 'id_espacio');
    }

  
}
