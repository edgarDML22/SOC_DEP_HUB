<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sobrescribe findToken() para cachear el resultado en archivo local.
 * Elimina las 2 queries a Neon que Sanctum hace en cada request autenticado.
 * TTL de 5 min: seguro porque logout() borra el token de DB y limpia el caché.
 */
class CachedPersonalAccessToken extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    public static function findToken($token): ?static
    {
        $hash = hash('sha256', $token);
        $key  = 'sanctum_token_' . $hash;

        return cache()->remember($key, now()->addMinutes(5), function () use ($token) {
            return parent::findToken($token);
        });
    }
}
