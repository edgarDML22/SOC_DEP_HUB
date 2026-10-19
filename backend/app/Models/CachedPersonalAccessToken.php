<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sobrescribe findToken() para cachear el resultado en Redis/caché.
 * Elimina las queries a Neon que Sanctum hace en cada request autenticado.
 * TTL de 24 horas: logout() revoca el token y limpia el caché de inmediato.
 */
class CachedPersonalAccessToken extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    public static function findToken($token): ?static
    {
        $hash = hash('sha256', $token);
        $key  = 'sanctum_token_' . $hash;

        return cache()->remember($key, now()->addHours(24), function () use ($token) {
            return parent::findToken($token);
        });
    }
}
