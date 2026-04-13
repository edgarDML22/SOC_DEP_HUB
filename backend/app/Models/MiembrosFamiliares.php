<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. IMPORTAR SOFT DELETES

class MiembrosFamiliares extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'miembros_familiares';
    protected $primaryKey = 'id_miembro';
    public $timestamps = false;

    protected $fillable = [
        'socio_id',
        'nombre_completo',
        'parentesco',
        'fecha_nacimiento',
        'genero',
        'correo'
    ];

    public function socioTitular()
    {
        return $this->belongsTo(SocioTitular::class, 'socio_id', 'id_socio');
    }

    public function pase()
    {
        return $this->hasOne(PasesDiarios::class, 'id_miembro', 'id_miembro');
    }
}