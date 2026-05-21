<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActividadPlantilla extends Model
{
    use SoftDeletes;
    protected $table = "actividades_plantilla";
    protected $primaryKey = 'id_actividad_plantilla';
    public $timestamps = false;
    //
    protected $fillable = [
        'id_sesion',
        'id_plantilla',
        'id_disciplina',
        'id_espacio',
        'id_instructor',
        'id_instructor_original',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'cupo_maximo',
        'requiere_inscripcion',
        'estatus',
    ];

    public function espacioFisico()
    {
        return $this->belongsTo(EspacioFisico::class, 'id_espacio', 'id_espacio');
    }

    public function plantilla()
    {
        return $this->belongsTo(PlantillaProgramacion::class, 'id_plantilla', 'id_plantilla');
    }

    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class, 'id_disciplina', 'id_disciplina');
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'id_instructor', 'id_instructor');
    }
}
