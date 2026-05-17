<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DraftProgramacion extends Model
{
    protected $table = 'drafts_programacion';
    protected $primaryKey = 'id';

    // Laravel usa created_at y updated_at por defecto
    public $timestamps = true;

    protected $fillable = [
        'id_gerente', // Actualizado según tu nueva columna
        'payload',
    ];

    protected $casts = [
        // El cast fundamental para que el JSON de Postgres se maneje como array en PHP
        'payload' => 'array',
    ];

    /**
     * Scope para encontrar el draft activo del gerente autenticado.
     * Nota: Lo renombré a delGerente por consistencia, pero si tu controlador 
     * estricto de la Task 23.1 llama a ->delUsuario(), puedes cambiarle el nombre.
     */
    public function scopeDelGerente($query, int $idGerente)
    {
        return $query->where('id_gerente', $idGerente);
    }

    /**
     * Relación con el gerente creador del draft.
     * Apunta al modelo Gerente usando id_gerente y referenciando id_empleado.
     */
    public function gerente()
    {
        // Asumiendo que el modelo se llama Gerente. 
        // Si tu modelo se llama distinto (ej. Empleado), cámbialo aquí.
        return $this->belongsTo(Gerente::class, 'id_gerente', 'id_empleado');
    }
}