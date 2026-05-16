<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreRegistroTorneo extends Model
{
    protected $table = 'pre_registros_torneo';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_torneo',
        'tipo',
        'estatus',
        'datos_participante',
        'urls_documentos',
        'motivo_rechazo'
    ];

    protected $casts = [
        'datos_participante' => 'array',
        'urls_documentos' => 'array',
    ];
}