<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActividadPlantilla extends Model
{
    protected $table = "actividades_plantilla";
    protected $primaryKey = 'id_actividad_plantilla';
    public $timestamps = false;
    //

    public function espacioFisico()
    {
        return $this->belongsTo(EspacioFisico::class, 'id_espacio', 'id_espacio');
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class, 'id_disciplina', 'id_disciplina');
    }
}
