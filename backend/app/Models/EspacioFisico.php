<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspacioFisico extends Model
{
    protected $table = "espacios_fisicos";
    protected $primaryKey = 'id_espacio';
    public $timestamps = false;

    public function reservaciones()
    {
        return $this->hasMany(Reservacion::class, 'id_espacio', 'id_espacio');
    }

    protected $fillable = [
        'nombre_espacio',
        'capacidad_maxima',
        'tipo_espacio',
        'es_reserva_on_demand',
        'es_clase_programada',
        'es_uso_libre',
        'estatus',
        'descripcion'
    ];



    public function disciplinas()
    {
        /* Parámetros: 
       1. Modelo destino
       2. Nombre exacto de la tabla pivote
       3. Llave foránea de ESTE modelo en el pivote
       4. Llave foránea del OTRO modelo en el pivote
        */
        return $this->belongsToMany(Disciplina::class, 'espacio_disciplina', 'id_espacio', 'id_disciplina');
    }
}
