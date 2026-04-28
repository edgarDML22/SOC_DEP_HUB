<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RegistrosLudoteca;
use App\Models\SocioTitular;
use Illuminate\Support\Facades\DB;
use App\Models\MongoDB\RegistroLudotecaMongo;

class WipeLudotecaDaily extends Command
{
    protected $signature = 'ludoteca:wipe-daily';
    protected $description = 'Wipe ludoteca';

    public function handle()
    {
        $activos = RegistrosLudoteca::where(
            'estatus_ludoteca',
            'ACTIVA'
        )->get();

        foreach ($activos as $registro) {

            // aumentar retraso al socio
            SocioTitular::where(
                'id_socio',
                $registro->id_adulto_ingreso
            )->increment('retrasos_ludoteca', 1);

            // marcar como entregado con retraso
            RegistrosLudoteca::where(
                'id_registro',
                $registro->id_registro
            )->update([
                        'estatus_ludoteca' => 'COMPLETADA_CON_RETRASO',
                        'hora_egreso' => now()
                    ]);

            // guardar auditoría en mongo
            RegistroLudotecaMongo::insert([
                'tutor_id' => $registro->id_adulto_ingreso,
                'menor_id' => $registro->id_menor,
                'hora_ingreso' => $registro->hora_ingreso,
                'hora_egreso' => now('America/Mexico_City'),
                'instructor_ingreso' => $registro->id_instructor_ingreso,
                'metadata' => [
                    'id_registro' => $registro->id_registro,
                    'estatus_final' => 'FORZADO_POR_SISTEMA'
                ],
            ]);
        }

        // limpiar tabla operativa
        RegistrosLudoteca::whereNotNull('hora_ingreso')
            ->update([
                'estatus_ludoteca' => 'INACTIVO',
                'hora_ingreso' => null,
                'hora_egreso' => null,
                'id_adulto_ingreso' => null,
                'id_adulto_egreso' => null,
                'id_instructor_ingreso' => null,
                'id_instructor_egreso' => null,
                'alerta_30_enviada' => false,
                'alerta_10_enviada' => false,
            ]);

        return 0;
    }
}