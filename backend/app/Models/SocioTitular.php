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

    protected $fillable = [
        'numero_accion',
        'tipo_socio',
        'modalidad_plan',
        'nombre_completo',
        'fecha_nacimiento',
        'genero',
        'correo_electronico',
        'estatus_cuenta',
        'contador_no_shows',
        'fecha_afiliacion',
        'retrasos_ludoteca',
        'fecha_fin_penalizacion_ludoteca',
        'fecha_fin_penalizacion_reserva',
        'estatus_penalizacion',
    ];

    protected $casts = [
        'fecha_nacimiento'               => 'date',
        'fecha_afiliacion'               => 'date',
        'fecha_fin_penalizacion_ludoteca' => 'datetime',
        'fecha_fin_penalizacion_reserva'  => 'datetime',
        'contador_no_shows'              => 'integer',
        'retrasos_ludoteca'              => 'integer',
    ];

    public function invitados()
    {
        return $this->hasMany(Invitados::class, 'socio_id', 'id_socio');
    }

    public function miembrosFamiliares()
    {
        return $this->hasMany(MiembrosFamiliares::class, 'socio_id', 'id_socio');
    }

    public function codigoQrActivo()
    {
        return $this->morphOne(CodigoQr::class, 'usuario', 'tipo_usuario', 'usuario_id')
                    ->where('estatus', 'ACTIVO'); 
    }

    public function codigosQr()
    {
        return $this->morphMany(CodigoQr::class, 'usuario', 'tipo_usuario', 'usuario_id');
    }

    public function routeNotificationForMail($notification)
    {
        return $this->correo_electronico;
    }
}