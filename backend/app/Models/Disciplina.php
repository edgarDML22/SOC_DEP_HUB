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
        'categorias_disciplina',
        'estatus',
    ];


}
