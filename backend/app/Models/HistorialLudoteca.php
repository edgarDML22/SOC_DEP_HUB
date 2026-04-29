<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialLudoteca extends Model
{
    protected $table = 'historial_ludoteca';
    protected $primaryKey = 'id_historial';
    public $timestamps = false;

    protected $fillable = [
        'id_historial',
        'id_registro_operativo',
        'id_menor',
        'id_adulto',
        'tiempo_total_minutos',
        'id_instructor_ingreso',
        'id_instrutor_egreso',
        'hora_egreso',
        'hora_ingreso',
        'calificacion_servicio',
        'comentarios_padre',
        'creado_el',
        'estatus_final'

    ];


}