<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TurnosLudoteca extends Model
{
    use HasFactory;

    protected $table = 'turnos_ludoteca';

    protected $primaryKey = 'id_turno';

    public $timestamps = false;

    protected $fillable = [
        'id_turno',
        'id_instructor',
        'fecha',
        'hora_inicio',
        'hora_fin'
    ];


}