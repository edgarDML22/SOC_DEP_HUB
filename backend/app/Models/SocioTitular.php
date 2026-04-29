<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SocioTitular extends Model
{
    use HasFactory, Notifiable;
    protected $table = 'socios_titulares';
    protected $primaryKey = 'id_socio';
    public $timestamps = false;

    // Solo estos campos se podrán editar mediante asignación masiva
    protected $fillable = [
        'fecha_nacimiento',
        'genero',
        'modalidad_plan',
        'retrasos_ludoteca',
        'contador_noshows',
        'estatus_cuenta',
        'fecha_fin_penalizacion'
    ];

    public function invitados()
    {
        return $this->hasMany(Invitados::class, 'socio_id', 'id_socio');
    }

    public function routeNotificationForMail($notification)
    {
        return $this->correo_electronico;
    }
}