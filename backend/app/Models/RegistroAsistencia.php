<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroAsistencia extends Model
{
    protected $table = 'registros_asistencia';
    protected $primaryKey = 'id_registro';
    
    // Desactivamos los timestamps de Laravel porque tu tabla solo tiene 
    // fecha_hora_registro y no tiene updated_at
    public $timestamps = false; 

    protected $fillable = [
        'id_sesion',
        'id_usuario',
        'tipo_usuario',
        'metodo_registro',
        'asistencia', 
    ];

    protected $casts = [
        'asistencia' => 'boolean',
        'fecha_hora_registro' => 'datetime',
    ];


    public function sesion()
    {
        return $this->belongsTo(SesionActiva::class, 'id_sesion', 'id_sesion');
    }

}