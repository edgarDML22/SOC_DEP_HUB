<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RegistrosLudoteca;
use App\Models\SocioTitular;
use Illuminate\Support\Facades\DB;
use App\Models\HistorialLudoteca;
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
            /* RegistroLudotecaMongo::insert([
                'tutor_id' => $registro->id_adulto_ingreso,
                'menor_id' => $registro->id_menor,
                'hora_ingreso' => $registro->hora_ingreso,
                'hora_egreso' => now('America/Mexico_City'),
                'instructor_ingreso' => $registro->id_instructor_ingreso,
                'metadata' => [
                    'id_registro' => $registro->id_registro,
                    'estatus_final' => 'c'
                ],
            ]); */
            //AGREGAR PARTE DE NUEVA TABLA
            $horaIngreso = \Carbon\Carbon::parse($registro->hora_ingreso);
            $horaEgreso = now();

            $tiempoTotal = (int) round(
                $horaIngreso->diffInMinutes($horaEgreso)
            );

            HistorialLudoteca::create([
                'id_registro_operativo' => $registro->id_registro,
                'id_menor' => $registro->id_menor,
                'id_adulto' => $registro->id_adulto_ingreso,
                'tiempo_total_minutos' => $tiempoTotal,
                'id_instructor_ingreso' => $registro->id_instructor_ingreso,
                'id_instructor_egreso' => $registro->id_instructor_ingreso,
                'hora_egreso' => $horaEgreso,
                'hora_ingreso' => $horaIngreso,
                'calificacion_servicio' => $registro->calificacion_servicio,
                'comentarios_padre' => $registro->comentarios_padre,
                'creado_el' => now(),
                'estatus_final' => 'FORZADO_POR_SISTEMA'
            ]);

        }

        // limpiar tabla operativa
        RegistrosLudoteca::whereDate('hora_ingreso', '<', now('America/Mexico_City')->toDateString())
            ->update([
                'estatus_ludoteca' => 'INACTIVO',
                'hora_ingreso' => null,
                'hora_egreso' => null,
                'id_adulto_egreso' => null,
                'id_instructor_egreso' => null,
                'alerta_30_enviada' => false,
                'alerta_10_enviada' => false,
            ]);
        $this->info('Wipe diario ejecutado correctamente');
        return 0;


    }
}