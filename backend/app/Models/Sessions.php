<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

    protected $table = 'sesiones';

    protected $primaryKey = 'id_sesion';

    protected $fillable = [
        'id_actividad_plantilla',
        'fecha_sesion',
        'id_instructor_sustituto',
        'capacidad_maxima',
        'estatus_sesion',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'id_instructor');
    }

    public function espacio()
    {
        return $this->belongsTo(EspacioFisico::class, 'id_espacio');
    }
}
