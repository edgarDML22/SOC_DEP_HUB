<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gerente extends Model
{
    // 1. Apuntar a la tabla correcta
    protected $table = 'gerentes';

    // 2. Definir la llave primaria personalizada (por defecto Laravel busca 'id')
    protected $primaryKey = 'id_empleado';

    // 3. Apagar los timestamps porque tu tabla no tiene created_at ni updated_at
    public $timestamps = false;

    // 4. Campos que se pueden insertar masivamente
    protected $fillable = [
        'nombre_completo',
        'correo_electronico',
        'cargo',
        'estatus',
    ];

    // 5. Casteos de tipos de datos para que PHP los maneje nativamente
    protected $casts = [
        'estatus' => 'boolean',
    ];

    /**
     * Relación inversa con DraftProgramacion (Opcional pero recomendada)
     * Un gerente puede tener muchos drafts (aunque por lógica de negocio lo limites a 1 activo)
     */
    public function drafts()
    {
        return $this->hasMany(DraftProgramacion::class, 'id_gerente', 'id_empleado');
    }
}