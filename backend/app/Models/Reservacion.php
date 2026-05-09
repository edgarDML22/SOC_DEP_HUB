<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservacion extends Model
{
    use HasFactory;
    protected $table = "reservaciones_on_demand";
    protected $primaryKey = 'id_reserva';
    public $timestamps = true;

    protected $fillable = [
        'id_socio_titular',
        'id_espacio',
        'id_disciplina',
        'fecha_reserva',
        'hora_inicio',
        'hora_fin',
        'estatus_operativo',
        'fecha_expiracion',
        'acompanantes_draft',
    ];

    protected $casts = [
        'acompanantes_draft' => 'array',
    ];

    public function espacioFisico(){
        return $this->belongsTo(EspacioFisico::class, 'id_espacio', 'id_espacio');
    }

    public function disciplina(){
        return $this->belongsTo(Disciplina::class, 'id_disciplina', 'id_disciplina');
    }
}
