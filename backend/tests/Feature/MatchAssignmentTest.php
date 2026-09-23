<?php

namespace Tests\Feature;

use App\Models\ActividadPlantilla;
use App\Models\EncuentrosTorneo;
use App\Models\SesionActiva;
use App\Models\Torneo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatchAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected int $idEspacio;
    protected int $idInstructor;
    protected int $idDisciplina;
    protected Torneo $torneo;
    protected EncuentrosTorneo $encuentro;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a Manager/Gerente user
        $this->adminUser = User::create([
            'email' => 'admin.torneos@club.com',
            'password' => bcrypt('password'),
            'rol' => 'gerente',
            'user_id' => 9999, // Dummy admin user
            'activo' => true,
        ]);

        // 2. Fetch existing space and instructor from DB to ensure validity
        $this->idEspacio = DB::table('espacios_fisicos')->value('id_espacio');
        $this->idInstructor = DB::table('instructores')->value('id_instructor');
        $this->idDisciplina = DB::table('disciplinas')->value('id_disciplina');

        // Ensure instructor is in instructor_disciplina
        DB::table('instructor_disciplina')->insertOrIgnore([
            'id_instructor' => $this->idInstructor,
            'id_disciplina' => $this->idDisciplina,
        ]);

        // Ensure space is in espacio_disciplina
        DB::table('espacio_disciplina')->insertOrIgnore([
            'id_espacio' => $this->idEspacio,
            'id_disciplina' => $this->idDisciplina,
        ]);

        // 3. Create a Tournament
        $this->torneo = Torneo::create([
            'nombre_torneo' => 'Torneo Test Colisiones',
            'id_disciplina' => $this->idDisciplina,
            'fecha_inicio' => '2026-06-01',
            'fecha_fin' => '2026-06-15',
            'estatus_torneo' => 'EN_PLANIFICACION',
            'cupo_maximo' => 16,
            'cupo_minimo' => 4,
            'tipo_acceso' => 'ABIERTO',
            'formato_competencia' => 'ELIMINACION_DIRECTA',
            'modalidad' => 'INDIVIDUAL',
            'genero_requerido' => 'MIXTO',
        ]);

        // 4. Create an Encounter
        $this->encuentro = EncuentrosTorneo::create([
            'id_torneo' => $this->torneo->id_torneo,
            'fase_bracket' => '16VOS',
            'numero_encuentro' => 1,
            'estatus_encuentro' => 'PENDIENTE',
        ]);
    }

    /**
     * Test: Assigning match succeeds when there are no collisions.
     */
    public function test_assign_match_success()
    {
        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/encuentros/{$this->encuentro->id_encuentro}/assign", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio,
                'fecha_hora_inicio' => '2026-06-01 10:00:00',
                'fecha_hora_fin' => '2026-06-01 11:30:00',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Encuentro asignado correctamente');

        $this->assertDatabaseHas('encuentros_torneo', [
            'id_encuentro' => $this->encuentro->id_encuentro,
            'id_espacio' => $this->idEspacio,
            'id_arbitro_asignado' => $this->idInstructor,
        ]);
    }

    /**
     * Test: Assigning match fails when space collides with another match.
     */
    public function test_assign_match_fails_on_other_match_collision()
    {
        // 1. Create another match already scheduled in the same space and time
        EncuentrosTorneo::create([
            'id_torneo' => $this->torneo->id_torneo,
            'fase_bracket' => '16VOS',
            'numero_encuentro' => 2,
            'estatus_encuentro' => 'PENDIENTE',
            'id_espacio' => $this->idEspacio,
            'id_arbitro_asignado' => $this->idInstructor,
            'fecha_hora_inicio' => '2026-06-01 10:00:00',
            'fecha_hora_fin' => '2026-06-01 11:30:00',
        ]);

        // 2. Try to assign the first match to the same space and time
        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/encuentros/{$this->encuentro->id_encuentro}/assign", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio,
                'fecha_hora_inicio' => '2026-06-01 10:30:00', // Overlaps!
                'fecha_hora_fin' => '2026-06-01 12:00:00',
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'La cancha ya está ocupada por otro encuentro de torneo en ese horario.']);
    }

    /**
     * Test: Assigning match fails when space collides with an active class session.
     */
    public function test_assign_match_fails_on_active_session_collision()
    {
        // 1. Create a class template in the same space and time
        $actividad = ActividadPlantilla::create([
            'id_disciplina' => $this->idDisciplina,
            'id_espacio' => $this->idEspacio,
            'id_instructor' => $this->idInstructor,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'cupo_maximo' => 15,
            'requiere_inscripcion' => true,
            'dia_semana' => 'LUNES',
            'estatus' => 'ACTIVO',
        ]);

        // 2. Create an active session of that class on Monday, June 1st, 2026
        SesionActiva::create([
            'id_actividad_plantilla' => $actividad->id_actividad_plantilla,
            'fecha_sesion' => '2026-06-01',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 0,
        ]);

        // 3. Try to assign tournament match overlapping this class session
        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/encuentros/{$this->encuentro->id_encuentro}/assign", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio,
                'fecha_hora_inicio' => '2026-06-01 10:15:00', // Overlaps!
                'fecha_hora_fin' => '2026-06-01 11:30:00',
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'La cancha ya está ocupada por una sesión de clase programada en ese horario.']);
    }

    /**
     * Test: Assigning match fails when space collides with a partner reservation.
     */
    public function test_assign_match_fails_on_partner_reservation_collision()
    {
        // 1. Create a partner reservation in the same space and time
        DB::table('reservaciones_on_demand')->insert([
            'id_socio_titular' => 1,
            'id_espacio' => $this->idEspacio,
            'id_disciplina' => $this->idDisciplina,
            'fecha_reserva' => '2026-06-01',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estatus_operativo' => 'ACTIVA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Try to assign tournament match overlapping this reservation
        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/v1/encuentros/{$this->encuentro->id_encuentro}/assign", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio,
                'fecha_hora_inicio' => '2026-06-01 09:30:00', // Overlaps!
                'fecha_hora_fin' => '2026-06-01 10:30:00',
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'La cancha ya está ocupada por una reserva activa de un socio en ese horario.']);
    }
}
