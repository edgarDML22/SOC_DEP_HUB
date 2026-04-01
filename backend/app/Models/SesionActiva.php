<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesionActiva extends Model
{
    protected $table = "sesiones_activas";
    protected $primaryKey = 'id_sesion';
    public $timestamps = false;
    //
}
