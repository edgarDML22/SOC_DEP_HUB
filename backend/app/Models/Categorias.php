<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorias extends Model
{
    protected $table = "categorias";
    protected $primaryKey = 'id_categoria';
    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'descripcion',
        'estatus'
    ];

    public function disciplinas()
    {
        return $this->hasMany(Disciplina::class, 'id_categoria', 'id_categoria');
    }
}