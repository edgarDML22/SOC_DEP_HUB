<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspacioFisico extends Model
{
    protected $table = "espacios_fisicos";
    public $timestamps = false;
    protected $primaryKey = 'id_espacio';
    //
}
