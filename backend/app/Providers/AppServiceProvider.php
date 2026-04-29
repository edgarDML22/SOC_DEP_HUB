<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation; // <-- 1. Importa esta clase arriba

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 2. Agrega este mapeo estricto
        Relation::enforceMorphMap([
            'SOCIO'    => 'App\Models\SocioTitular',
            'FAMILIAR' => 'App\Models\MiembrosFamiliares',
            // Agrega el de invitados cuando implementes ese modelo:
            // 'EXTERNO'  => 'App\Models\Invitados', 
        ]);
    }
}