<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SesionActiva;

class InscripcionClase extends Model
{
    protected $table = "inscripciones_clases";
    protected $primaryKey = 'id_inscripcion';
    public $timestamps = false;
    //

    protected $fillable = [
        'estatus_inscripcion'
    ];

    public function sesion()
    {
        return $this->belongsTo(SesionActiva::class, 'id_sesion', 'id_sesion');
    }
}
