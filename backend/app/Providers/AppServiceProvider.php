<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Sanctum\Sanctum;
use App\Models\CachedPersonalAccessToken;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Neon.tech: sobreescribir PostgresConnector para inyectar endpoint ID
        // en el DSN cuando el cliente libpq local no soporta SNI.
        // Solo actúa si NEON_ENDPOINT_ID está definido en .env.
        if ($endpointId = env('NEON_ENDPOINT_ID')) {
            $this->app->bind('db.connector.pgsql', function () use ($endpointId) {
                return new class($endpointId) extends PostgresConnector {
                    public function __construct(private string $endpointId) {}

                    protected function getDsn(array $config): string
                    {
                        $dsn = parent::getDsn($config);
                        // Neon requiere el endpoint en el parámetro options envuelto en comillas simples
                        // para que PDO no lo interprete como una opción de conexión inválida.
                        return $dsn . ";options='endpoint=" . $this->endpointId . "'";
                    }
                };
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(CachedPersonalAccessToken::class);

        Relation::enforceMorphMap([
            'USER'         => 'App\\Models\\User',
            'SOCIO'        => 'App\\Models\\SocioTitular',
            'FAMILIAR'     => 'App\\Models\\MiembrosFamiliares',
            'PARTICIPANTE' => 'App\\Models\\ParticipantesTorneo',
        ]);
    }
}