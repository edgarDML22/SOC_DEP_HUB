<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class NoShowOnDemandTest extends TestCase
{
    use DatabaseTransactions;

    protected int $idSocio;
    protected int $idEspacio;
    protected int $idDisciplina;

    protected function setUp(): void
    {
        parent::setUp();

        $this->idSocio = DB::table('socios_titulares')->value('id_socio') ?? DB::table('socios_titulares')->insertGetId([
            'numero_accion' => 'TEST-NS-01',
            'tipo_socio' => 'TITULAR',
            'modalidad_plan' => 'INDIVIDUAL',
            'nombre_completo' => 'Socio Test NoShow',
            'fecha_nacimiento' => '1990-01-01',
            'genero' => 'M',
            'correo_electronico' => 'test_noshow@test.com',
            'estatus_cuenta' => 'ACTIVO',
            'contador_no_shows' => 0,
            'fecha_afiliacion' => '2020-01-01',
        ], 'id_socio');

        $this->idEspacio = DB::table('espacios_fisicos')->value('id_espacio');
        $this->idDisciplina = DB::table('disciplinas')->value('id_disciplina');
    }

    public function test_marca_no_show_reservas_pasadas_y_de_hoy_vencidas(): void
    {
        $tz = 'America/Mexico_City';
        $now = Carbon::now($tz);

        // 1. Reserva pasada (de hace 2 días) en estado ACTIVA
        $idReservaPasada = DB::table('reservaciones_on_demand')->insertGetId([
            'id_socio_titular' => $this->idSocio,
            'id_espacio' => $this->idEspacio,
            'id_disciplina' => $this->idDisciplina,
            'fecha_reserva' => $now->copy()->subDays(2)->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estatus_operativo' => 'ACTIVA',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'id_reserva');

        // 2. Reserva de hoy ya vencida (> 15 min después de hora_fin)
        // Fijamos hora_fin a 30 minutos en el pasado si es posible dentro del día de hoy
        $horaFinHoyVencida = $now->copy()->subMinutes(30);
        $horaInicioHoyVencida = $horaFinHoyVencida->copy()->subHour();
        $idReservaHoyVencida = null;

        if ($horaFinHoyVencida->toDateString() === $now->toDateString()) {
            $idReservaHoyVencida = DB::table('reservaciones_on_demand')->insertGetId([
                'id_socio_titular' => $this->idSocio,
                'id_espacio' => $this->idEspacio,
                'id_disciplina' => $this->idDisciplina,
                'fecha_reserva' => $now->toDateString(),
                'hora_inicio' => $horaInicioHoyVencida->format('H:i:s'),
                'hora_fin' => $horaFinHoyVencida->format('H:i:s'),
                'estatus_operativo' => 'ACTIVA',
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_reserva');
        }

        // 3. Reserva futura (mañana)
        $idReservaFutura = DB::table('reservaciones_on_demand')->insertGetId([
            'id_socio_titular' => $this->idSocio,
            'id_espacio' => $this->idEspacio,
            'id_disciplina' => $this->idDisciplina,
            'fecha_reserva' => $now->copy()->addDay()->toDateString(),
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estatus_operativo' => 'ACTIVA',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'id_reserva');

        // Ejecutar el comando
        Artisan::call('app:no-show-on-demand');

        // Aserciones
        $reservaPasada = DB::table('reservaciones_on_demand')->where('id_reserva', $idReservaPasada)->first();
        $this->assertEquals('NO_SHOW', $reservaPasada->estatus_operativo, 'La reserva de fecha pasada debió actualizarse a NO_SHOW');

        if ($idReservaHoyVencida) {
            $reservaHoy = DB::table('reservaciones_on_demand')->where('id_reserva', $idReservaHoyVencida)->first();
            $this->assertEquals('NO_SHOW', $reservaHoy->estatus_operativo, 'La reserva vencida de hoy debió actualizarse a NO_SHOW');
        }

        $reservaFutura = DB::table('reservaciones_on_demand')->where('id_reserva', $idReservaFutura)->first();
        $this->assertEquals('ACTIVA', $reservaFutura->estatus_operativo, 'La reserva futura debe mantenerse ACTIVA');
    }
}

