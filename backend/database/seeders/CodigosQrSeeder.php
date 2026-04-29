<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\SocioTitular;
use App\Models\MiembroFamiliar;
use App\Models\CodigoQr;
use App\Models\MiembrosFamiliares;

class CodigosQrSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando generación de Códigos QR estáticos...');

        try {
            // === INICIA LA TRANSACCIÓN ===
            DB::transaction(function () {
                
                // ---------------------------------------------------------
                // 1. PROCESAR SOCIOS TITULARES
                // ---------------------------------------------------------
                $socios = SocioTitular::all();
                $contadorSocios = 0;

                foreach ($socios as $socio) {
                    $tieneQr = CodigoQr::where('usuario_id', $socio->id_socio)
                                       ->where('tipo_usuario', 'SOCIO')
                                       ->where('estatus', 'ACTIVO')
                                       ->exists();

                    if (!$tieneQr) {
                        do {
                            $codigoQR = 'QS' . substr(str_replace('-', '', Str::uuid()), 0, 6);
                        } while (CodigoQr::where('codigo', $codigoQR)->exists()); 

                        CodigoQr::create([
                            'codigo'            => $codigoQR,
                            'usuario_id'        => $socio->id_socio,
                            'tipo_usuario'   => 'SOCIO',
                            'fecha_activacion'  => now(),
                            'estatus' => 'ACTIVO',
                        ]);

                        $contadorSocios++;
                    }
                }
                $this->command->info("Se crearon {$contadorSocios} códigos para Socios Titulares.");

                // ---------------------------------------------------------
                // 2. PROCESAR MIEMBROS FAMILIARES
                // ---------------------------------------------------------
                $familiares = MiembrosFamiliares::all();
                $contadorFamiliares = 0;

                foreach ($familiares as $familiar) {
                    $tieneQr = CodigoQr::where('usuario_id', $familiar->id_miembro)
                                       ->where('tipo_usuario', 'FAMILIAR')
                                       ->where('estatus', 'ACTIVO')
                                       ->exists();

                    if (!$tieneQr) {
                        do {
                            $codigoQR = 'MF' . substr(str_replace('-', '', Str::uuid()), 0, 6);
                        } while (CodigoQr::where('codigo', $codigoQR)->exists());

                        CodigoQr::create([
                            'codigo'            => $codigoQR,
                            'usuario_id'        => $familiar->id_miembro,
                            'tipo_usuario'   => 'FAMILIAR',
                            'fecha_activacion'  => now(),
                            'estatus' => 'ACTIVO',
                        ]);

                        $contadorFamiliares++;
                    }
                }
                $this->command->info("Se crearon {$contadorFamiliares} códigos para Miembros Familiares.");

            });
            // === TERMINA LA TRANSACCIÓN ===

            $this->command->info('¡Proceso completado exitosamente!');

        } catch (\Exception $e) {
            $this->command->error('Ocurrió un error durante la transacción: ' . $e->getMessage());
        }
    }
}