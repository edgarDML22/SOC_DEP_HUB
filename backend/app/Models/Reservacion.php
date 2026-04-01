<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservacion extends Model
{
    protected $table = "reservaciones_on_demand";
    protected $primaryKey = 'id_reservacion';
    public $timestamps = true;
}
