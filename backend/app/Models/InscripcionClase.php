<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InscripcionClase extends Model
{
    protected $table = "inscripciones_clases";
    protected $primaryKey = 'id_inscripcion';
    public $timestamps = false;
    //

    protected $fillable = [
        'estatus_inscripcion'
    ];
}
