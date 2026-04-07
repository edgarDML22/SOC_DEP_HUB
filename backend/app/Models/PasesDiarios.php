<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasesDiarios extends Model
{
    use HasFactory;

    protected $table = 'pases_diarios';

    protected $fillable = [
        'id_pase',
        'invitado_id',
        'estatus_accceso',

    ];
}