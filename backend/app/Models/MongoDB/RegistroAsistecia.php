<?php

namespace App\Models\MongoDB;

use MongoDB\Laravel\Eloquent\Model;


class RegistroAsistecia extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'telemetria_asistencia';
    public $timestamps = false;
    protected $fillable = [
        'socio_id',
        'id_sesion',
        'fase',
        'timestamp',
        'metadata'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];
}