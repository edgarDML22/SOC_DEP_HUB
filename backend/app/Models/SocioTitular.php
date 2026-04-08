<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocioTitular extends Model
{
    protected $table = 'socios_titulares';
    protected $primaryKey = 'id_socio';
    public $timestamps = false;

    // Solo estos campos se podrán editar mediante asignación masiva
    protected $fillable = [
        'fecha_nacimiento',
        'genero',
    ];

    public function invitados()
    {
        return $this->hasMany(Invitados::class, 'socio_id', 'id_socio');
    }
}