<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SocioTitular;

class RegistrosLudoteca extends Model
{
    use HasFactory;

    protected $table = 'registros_ludoteca';
    public $timestamps = false;
    protected $primaryKey = 'id_registro';

    protected $fillable = [
        'id_menor',
        'id_adulto_ingreso',
        'id_adulto_egreso',
        'hora_ingreso',
        'hora_egreso',
        'estatus_visita',
        'id_instructor_ingreso',
        'id_instructor_egreso',
    ];


    public function adultoIngreso()
    {
        return $this->belongsTo(SocioTitular::class, 'id_adulto_ingreso', 'id_socio');
    }

    public function adultoEgreso()
    {
        return $this->belongsTo(SocioTitular::class, 'id_adulto_egreso', 'id_socio');
    }

    public function menor()
    {
        return $this->belongsTo(MiembrosFamiliares::class, 'id_menor', 'id_miembro');
    }
}