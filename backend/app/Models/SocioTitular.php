<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocioTitular extends Model
{
    // 1. Configuración de la base de datos
    protected $table = 'socios_titulares';
    protected $primaryKey = 'id_socio';
    public $timestamps = false;

    // 2. Asignación masiva (Alineado estrictamente a tu Diccionario de Datos)
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
        'fecha_fin_penalizacion',
    ];

    // 3. Conversión de tipos (Casts) para facilitar su uso en Vue/Controllers
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_afiliacion' => 'date',
        'fecha_fin_penalizacion' => 'datetime',
        'contador_no_shows' => 'integer',
        'retrasos_ludoteca' => 'integer',
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
}