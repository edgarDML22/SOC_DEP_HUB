<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaTorneo extends Model
{
    protected $table = "categorias_torneo";
    protected $primaryKey = 'id_categoria';
    public $timestamps = false;
    protected $fillable = [
        'id_categoria',
        'nombre_categoria',
        'edad_minima',
        'edad_maxima',
        'genero_requerido',
    ];
}