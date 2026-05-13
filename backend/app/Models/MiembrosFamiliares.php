<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\RegistrosLudoteca;

class MiembrosFamiliares extends Model
{
    use HasFactory, SoftDeletes;

    // 1. Configuración de la base de datos
    protected $table = 'miembros_familiares';
    protected $primaryKey = 'id_miembro';
    
    // Apagamos los timestamps porque tu diccionario de datos NO tiene created_at ni updated_at
    // Nota: SoftDeletes seguirá funcionando bien porque sí tienes la columna deleted_at
    public $timestamps = false;

    // 2. Asignación masiva (Alineado estrictamente a tu Diccionario de Datos)
    protected $fillable = [
        'socio_id',
        'parentesco',
        'nombre_completo',
        'fecha_nacimiento',
        'genero',
        'correo',
        'contador_no_shows'
    ];

    // 3. Conversión de tipos (Casts)
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'contador_no_shows' => 'integer',
    ];

    // ==========================================
    // RELACIONES TRADICIONALES
    // ==========================================

    /**
     * Relación con el Socio Titular que paga la membresía.
     */
    public function socio()
    {
        return $this->belongsTo(SocioTitular::class, 'socio_id', 'id_socio');
    }

    /**
     * Alias por retrocompatibilidad por si usabas socioTitular() en otra parte de tu código.
     */
    public function socioTitular()
    {
        return $this->belongsTo(SocioTitular::class, 'socio_id', 'id_socio');
    }


    public function registrosLudoteca()
    {
        return $this->hasMany(RegistrosLudoteca::class, 'id_menor', 'id_miembro');
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