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

    public function espacios()
    {
        /* Parámetros: 
       1. Modelo destino
       2. Nombre exacto de la tabla pivote
       3. Llave foránea de ESTE modelo en el pivote
       4. Llave foránea del OTRO modelo en el pivote
        */
        return $this->belongsToMany(EspacioFisico::class, 'espacio_disciplina', 'id_disciplina', 'id_espacio');
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

    public function categoria()
    {
        return $this->belongsTo(CategoriaDisciplina::class, 'id_categoria', 'id');
    }

    public function instructores()
    {
        return $this->belongsToMany(Instructor::class, 'instructor_disciplina', 'id_disciplina', 'id_instructor');
    }
}
