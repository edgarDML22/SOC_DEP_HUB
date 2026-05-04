<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaDisciplina extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'id_categoria';
    protected $fillable = ['nombre', 'descripcion', 'estatus'];

    public function disciplinas()
    {
        return $this->belongsToMany(Disciplina::class, 'categoria_disciplina', 'id_categoria', 'id_disciplina');
    }
}
