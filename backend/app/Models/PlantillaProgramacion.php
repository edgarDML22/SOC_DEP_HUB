<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaProgramacion extends Model
{
    protected $table = "plantillas_programacion";
    protected $primaryKey = 'id_plantilla';

    public $timestamps = false;

    protected $fillable = [
        'nombre_plantilla',
        'fecha_inicio',
        'fecha_fin',
        'estatus_plantilla',
    ];

    public function actividades()
    {
        return $this->hasMany(ActividadPlantilla::class, 'id_plantilla', 'id_plantilla');
    }
}
