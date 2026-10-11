<?php

namespace Tests\Unit;

use App\Models\ActividadPlantilla;
use App\Models\PlantillaProgramacion;
use App\Models\SesionActiva;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UpdateSessionStatusTest extends TestCase
{
    use DatabaseTransactions;

    protected ActividadPlantilla $actividad;
    protected PlantillaProgramacion $plantilla;

    protected function setUp(): void
    {
        parent::setUp();

        $idDisciplina = \Illuminate\Support\Facades\DB::table('disciplinas')->insertGetId([
            'nombre_disciplina' => 'Test Discipline ' . uniqid(),
            'estatus'           => 'ACTIVO',
        ], 'id_disciplina');

        $idEspacio = \Illuminate\Support\Facades\DB::table('espacios_fisicos')->value('id_espacio');
        $idInstructor = \Illuminate\Support\Facades\DB::table('instructores')->value('id_instructor');

        $this->plantilla = PlantillaProgramacion::create([
            'nombre_plantilla'  => 'Plantilla Test Vencida',
            'fecha_inicio'      => '2026-05-01',
            'fecha_fin'         => '2026-05-07',
            'estatus_plantilla' => true,
            'publicada'         => true,
        ]);

        $this->actividad = ActividadPlantilla::create([
            'id_plantilla'         => $this->plantilla->id_plantilla,
            'id_disciplina'        => $idDisciplina,
            'id_espacio'           => $idEspacio,
            'id_instructor'        => $idInstructor,
            'hora_inicio'          => '10:00:00',
            'hora_fin'             => '11:00:00',
            'cupo_maximo'          => 20,
            'requiere_inscripcion' => true,
            'dia_semana'           => 'LUNES',
            'estatus'              => 'ACTIVO',
        ]);
    }

    public function test_sesiones_pasadas_y_plantillas_vencidas_se_actualizan_correctamente(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 12, 0, 0, 'America/Mexico_City'));

        // Sesión de hace meses atrapada en DISPONIBLE
        $sesionPasada = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividad->id_actividad_plantilla,
            'fecha_sesion'           => '2026-05-04',
            'estatus_sesion'         => 'DISPONIBLE',
            'cantidad_inscritos'     => 0,
            'hora_inicio'            => '10:00:00',
            'hora_fin'               => '11:00:00',
        ]);

        // Sesión de hace meses atrapada en EN_CURSO
        $sesionEnCursoPasada = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividad->id_actividad_plantilla,
            'fecha_sesion'           => '2026-05-05',
            'estatus_sesion'         => 'EN_CURSO',
            'cantidad_inscritos'     => 0,
            'hora_inicio'            => '12:00:00',
            'hora_fin'               => '13:00:00',
        ]);

        $this->artisan('sessions:update-status')
            ->assertExitCode(0);

        // Ambas sesiones pasadas deben haber sido marcadas FINALIZADA
        $this->assertEquals('FINALIZADA', $sesionPasada->fresh()->estatus_sesion);
        $this->assertEquals('FINALIZADA', $sesionEnCursoPasada->fresh()->estatus_sesion);

        // La plantilla con fecha_fin en mayo debe haber vuelto a su estado base
        $plantillaFresca = $this->plantilla->fresh();
        $this->assertFalse((bool) $plantillaFresca->publicada);
        $this->assertFalse((bool) $plantillaFresca->estatus_plantilla);
        $this->assertNull($plantillaFresca->fecha_inicio);
        $this->assertNull($plantillaFresca->fecha_fin);

        Carbon::setTestNow();
    }
}
