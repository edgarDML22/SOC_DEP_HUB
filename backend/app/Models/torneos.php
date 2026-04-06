<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class torneos extends Model
{
    protected $table = "torneos";
    protected $primaryKey = 'id_torneo';
    public $timestamps = false;
    protected $fillable = [
        'id_disciplina',
        'fecha_inicio',
        'fecha_fin',
        'cupo_maximo',
        'tipo_acceso',
        'nombre_torneo',
        'formato_competencia',
        'descripcion',
        'estatus_torneo',
        'id_categoria',
    ];
    public function disciplina()
    {
        return $this->belongsTo(Disciplina::class, 'id_disciplina');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaTorneo::class, 'id_categoria');
    }
}