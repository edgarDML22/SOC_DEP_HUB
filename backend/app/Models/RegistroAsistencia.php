<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class RegistroAsistencia extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'registro_asistencias';
}