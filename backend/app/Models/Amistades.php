<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Amistades extends Model
{
    use HasFactory;

    protected $table = 'amistades';
    protected $primaryKey = 'id_amistad';
    public $timestamps = false;
    protected $fillable = [
        'solicitante_id',
        'receptor_id',
        'estado',
        'created_at',
        'updated_at'
    ];

    public function receptor()
    {
        return $this->belongsTo(SocioTitular::class, 'receptor_id', 'id_socio');
    }

    public function solicitante()
    {
        return $this->belongsTo(SocioTitular::class, 'solicitante_id', 'id_socio');
    }
}