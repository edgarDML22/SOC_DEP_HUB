<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaDisciplina extends Model
{
    protected $table = 'categorias_disciplinas';
    protected $fillable = ['nombre_categoria', 'descripcion_categoria'];

    public function disciplinas()
    {
        return $this->hasMany(Disciplina::class, 'id_categoria', 'id');
    }
}
