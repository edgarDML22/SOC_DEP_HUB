<?php

namespace App\Models\MongoDB;

use MongoDB\Laravel\Eloquent\Model;

class RegistroLudotecaMongo extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'eventos_ludoteca';

    public $timestamps = false;

    protected $fillable = [
        'tutor_id',
        'menor_id',
        'tipo_evento',
        'timestamp',
        'metadata',
        'hora_ingreso',
        'hora_egreso',
        'instructor_ingreso',
        'instructor_egreso'

    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];
}