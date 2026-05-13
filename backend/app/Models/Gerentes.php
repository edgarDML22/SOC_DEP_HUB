<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gerentes extends Model
{
    protected $table = 'gerentes';
    protected $primaryKey = 'id_empleado';
    public $timestamps = false;

    protected $fillable = [
        'id_empleado',
        'nombre_completo',
        'correo_electronico',
        'cargo',
        'password',

    ];
}