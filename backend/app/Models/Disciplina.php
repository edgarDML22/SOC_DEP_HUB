<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disciplina extends Model
{
    protected $table = 'disciplinas';
    public $timestamps = false;
    protected $primaryKey = 'id_disciplina';
    //
    protected $fillable = [
        'nombre_disciplina',
        'id_categoria',
        'categoria_disciplina',
        'descripcion',
        'estatus',
    ];

    public function espacios(){
        return $this->belongsToMany(EspacioFisico::class, 'espacio_disciplina', 'id_disciplina', 'id_espacio');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaDisciplina::class, 'id_categoria', 'id');
    }

    public function instructores()
    {
        return $this->belongsToMany(Instructor::class, 'instructor_disciplina', 'id_disciplina', 'id_instructor');
    }
}
