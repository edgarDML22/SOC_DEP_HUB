<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // <-- Laravel clásico
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Al no especificar $connection, Laravel usará la default (pgsql)
    protected $fillable = [
        'email', 'password', 'rol', 'user_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function instructor()
    {
        // La tabla `instructores` no tiene `id_usuario`.
        // El vínculo es: users.user_id → instructores.id_instructor
        return $this->hasOne(Instructor::class, 'id_instructor', 'user_id');
    }
}