<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <-- 1. Importación vital para el Token

class User extends Authenticatable
{
    // 2. Aquí se agrega HasApiTokens para que el AuthController pueda usar createToken()
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Los atributos que se pueden asignar masivamente.
     * Actualizados para coincidir con nuestra nueva tabla SSO.
     */
    protected $fillable = [
        'email',
        'password',
        'rol',
        'perfil_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Los atributos que deben castearse a tipos nativos.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}