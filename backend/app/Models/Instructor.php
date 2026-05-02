<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    // 1. Sobreescribir convenciones de Laravel
    protected $table = 'instructores';
    protected $primaryKey = 'id_instructor';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nombre_completo',
        'telefono',
        'correo_electronico',
        'estatus',
        'fecha_afiliacion',
        'fecha_nacimiento',
        'hora_entrada',
        'hora_salida'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    

    public function actividades()
    {
        return $this->hasMany(ActividadPlantilla::class, 'id_instructor', 'id_instructor');
    }

    public function actividadesOriginales()
    {
        return $this->hasMany(ActividadPlantilla::class, 'id_instructor_original', 'id_instructor');
    }
    public function disciplinas()
    {
        return $this->belongsToMany(
            Disciplina::class,
            'instructor_disciplina',
            'id_instructor',
            'id_disciplina'
        );
    }
}