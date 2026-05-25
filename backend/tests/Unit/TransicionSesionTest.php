<?php

namespace Tests\Unit;

use App\Models\ActividadPlantilla;
use App\Models\SesionActiva;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TransicionSesionTest extends TestCase
{
    use DatabaseTransactions;

    protected ActividadPlantilla $actividad;
    protected int $idEspacio;
    protected int $idInstructor;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear una Disciplina base
        $idDisciplina = \Illuminate\Support\Facades\DB::table('disciplinas')->insertGetId([
            'nombre_disciplina' => 'Yoga Unit Test',
            'estatus' => 'ACTIVO',
        ], 'id_disciplina');

        $this->idEspacio = \Illuminate\Support\Facades\DB::table('espacios_fisicos')->value('id_espacio');
        $this->idInstructor = \Illuminate\Support\Facades\DB::table('instructores')->value('id_instructor');

        // Crear una Actividad Plantilla base
        $this->actividad = ActividadPlantilla::create([
            'id_disciplina' => $idDisciplina,
            'id_espacio' => $this->idEspacio,
            'id_instructor' => $this->idInstructor,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cupo_maximo' => 20,
            'requiere_inscripcion' => true,
            'dia_semana' => 'LUNES',
            'estatus' => 'ACTIVO',
        ]);
    }

    /**
     * Test: `sesiones:iniciar` cambia `DISPONIBLE` a `EN_CURSO` en la ventana de 15 minutos.
     */
    public function test_comando_iniciar_sesiones_inicia_correctamente()
    {
        // Fijar tiempo a las 09:50 (falta 10 minutos para las 10:00)
        Carbon::setTestNow(Carbon::create(2026, 5, 25, 9, 50, 0, 'America/Mexico_City'));

        $sesionIniciar = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividad->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-25',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 0,
        ]);

        // Sesión que empieza muy tarde (por ejemplo a las 11:00, no en el rango de 15 min)
        $actividadTardia = ActividadPlantilla::create([
            'id_disciplina' => $this->actividad->id_disciplina,
            'id_espacio' => $this->idEspacio,
            'id_instructor' => $this->idInstructor,
            'hora_inicio' => '11:00:00',
            'hora_fin' => '12:00:00',
            'cupo_maximo' => 20,
            'requiere_inscripcion' => true,
            'dia_semana' => 'LUNES',
            'estatus' => 'ACTIVO',
        ]);

        $sesionNoIniciar = SesionActiva::create([
            'id_actividad_plantilla' => $actividadTardia->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-25',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 0,
        ]);

        // Ejecutar el comando artisan
        $this->artisan('sesiones:iniciar')
            ->expectsOutputToContain('1 sesión(es) iniciadas.')
            ->assertExitCode(0);

        // Verificar estados
        $this->assertEquals('EN_CURSO', $sesionIniciar->fresh()->estatus_sesion);
        $this->assertEquals('DISPONIBLE', $sesionNoIniciar->fresh()->estatus_sesion);

        Carbon::setTestNow();
    }

    /**
     * Test: `sesiones:finalizar` cambia `EN_CURSO` a `FINALIZADA` 20 minutos después de `hora_fin`.
     */
    public function test_comando_finalizar_sesiones_finaliza_correctamente()
    {
        // Fijar tiempo a las 11:25 (han pasado 25 minutos desde las 11:00)
        Carbon::setTestNow(Carbon::create(2026, 5, 25, 11, 25, 0, 'America/Mexico_City'));

        $sesionFinalizar = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividad->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-25',
            'estatus_sesion' => 'EN_CURSO',
            'cantidad_inscritos' => 0,
        ]);

        // Sesión que termina a las 11:15 (solo han pasado 10 minutos desde hora_fin, no debe finalizar)
        $actividadCorta = ActividadPlantilla::create([
            'id_disciplina' => $this->actividad->id_disciplina,
            'id_espacio' => $this->idEspacio,
            'id_instructor' => $this->idInstructor,
            'hora_inicio' => '10:30:00',
            'hora_fin' => '11:15:00',
            'cupo_maximo' => 20,
            'requiere_inscripcion' => true,
            'dia_semana' => 'LUNES',
            'estatus' => 'ACTIVO',
        ]);

        $sesionNoFinalizar = SesionActiva::create([
            'id_actividad_plantilla' => $actividadCorta->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-25',
            'estatus_sesion' => 'EN_CURSO',
            'cantidad_inscritos' => 0,
        ]);

        // Ejecutar el comando artisan
        $this->artisan('sesiones:finalizar')
            ->expectsOutputToContain('1 sesión(es) finalizadas.')
            ->assertExitCode(0);

        // Verificar estados
        $this->assertEquals('FINALIZADA', $sesionFinalizar->fresh()->estatus_sesion);
        $this->assertEquals('EN_CURSO', $sesionNoFinalizar->fresh()->estatus_sesion);

        Carbon::setTestNow();
    }
}
