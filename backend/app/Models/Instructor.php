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
        'estatus',
        'fecha_contratacion',
        'fecha_nacimiento'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function disciplinas()
    {
        return $this->belongsToMany(Disciplina::class, 'instructor_disciplina', 'id_instructor', 'id_disciplina');
    }
}