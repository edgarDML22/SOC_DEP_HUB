<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspacioFisico extends Model
{
    protected $table = "espacios_fisicos";
    protected $primaryKey = 'id_espacio';
    public $timestamps = false;

    public function reservaciones(){
        return $this->hasMany(Reservacion::class, 'id_espacio', 'id_espacio');
    }
}
