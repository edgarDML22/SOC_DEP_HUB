<?php

namespace Tests\Feature;

use App\Models\ActividadPlantilla;
use App\Models\EncuentrosTorneo;
use App\Models\EspacioFisico;
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
    protected int $idEspacio2;
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
            'user_id' => 9999,
            'activo' => true,
        ]);

        // 2. Fetch or create discipline
        $this->idDisciplina = DB::table('disciplinas')->value('id_disciplina') ?? DB::table('disciplinas')->insertGetId([
            'nombre' => 'Tenis',
            'descripcion' => 'Tenis test',
            'estatus' => 'ACTIVO',
        ]);

        // 3. Create isolated spaces
        $this->idEspacio = DB::table('espacios_fisicos')->insertGetId([
            'nombre_espacio' => 'Cancha Test 1',
            'capacidad_maxima' => 4,
            'es_reserva_on_demand' => true,
            'es_clase_programada' => true,
            'es_uso_libre' => false,
            'estatus' => 'ACTIVO',
        ], 'id_espacio');

        $this->idEspacio2 = DB::table('espacios_fisicos')->insertGetId([
            'nombre_espacio' => 'Cancha Test 2',
            'capacidad_maxima' => 4,
            'es_reserva_on_demand' => true,
            'es_clase_programada' => true,
            'es_uso_libre' => false,
            'estatus' => 'ACTIVO',
        ], 'id_espacio');

        // 4. Create isolated instructor
        $this->idInstructor = DB::table('instructores')->insertGetId([
            'nombre_completo' => 'Árbitro Test',
            'correo_electronico' => 'arbitro.test.' . uniqid() . '@club.com',
            'estatus' => 'ACTIVO',
        ], 'id_instructor');

        // Ensure instructor is in instructor_disciplina
        DB::table('instructor_disciplina')->insertOrIgnore([
            'id_instructor' => $this->idInstructor,
            'id_disciplina' => $this->idDisciplina,
        ]);

        // Ensure spaces are in espacio_disciplina
        DB::table('espacio_disciplina')->insertOrIgnore([
            'id_espacio' => $this->idEspacio,
            'id_disciplina' => $this->idDisciplina,
        ]);

        DB::table('espacio_disciplina')->insertOrIgnore([
            'id_espacio' => $this->idEspacio2,
            'id_disciplina' => $this->idDisciplina,
        ]);

        // 5. Create a Tournament
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

        // 6. Create an Encounter
        $this->encuentro = EncuentrosTorneo::create([
            'id_torneo' => $this->torneo->id_torneo,
            'fase_bracket' => '16VOS',
            'numero_encuentro' => 1,
            'estatus_encuentro' => 'PENDIENTE',
        ]);
    }

    /**
     * Test: Assigning match succeeds when there are no collisions using PATCH /api/v1/encuentros/{id}/assign.
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
            'estatus_encuentro' => 'PENDIENTE',
        ]);
    }

    /**
     * Test: Assigning match succeeds using POST /api/torneos/encuentros/{id}/asignar endpoint contract.
     */
    public function test_assign_match_post_endpoint_contract()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/torneos/encuentros/{$this->encuentro->id_encuentro}/asignar", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio,
                'fecha_hora_inicio' => '2026-10-15 10:00:00',
                'fecha_hora_fin' => '2026-10-15 11:30:00',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Encuentro asignado correctamente')
            ->assertJsonStructure([
                'message',
                'encuentro' => [
                    'id_encuentro',
                    'id_arbitro_asignado',
                    'id_espacio',
                    'fecha_hora_inicio',
                    'fecha_hora_fin',
                    'estatus_encuentro',
                ],
            ]);
    }

    /**
     * Test: Simulating concurrent / race-condition assignment attempts on the same court/schedule.
     * One must succeed with 200 OK and the second must be rejected with 409 Conflict.
     */
    public function test_concurrent_assignments_race_condition_protection()
    {
        // Create a second encounter in the same tournament
        $encuentro2 = EncuentrosTorneo::create([
            'id_torneo' => $this->torneo->id_torneo,
            'fase_bracket' => '16VOS',
            'numero_encuentro' => 2,
            'estatus_encuentro' => 'PENDIENTE',
        ]);

        $payload = [
            'id_arbitro' => $this->idInstructor,
            'id_espacio' => $this->idEspacio,
            'fecha_hora_inicio' => '2026-06-01 10:00:00',
            'fecha_hora_fin' => '2026-06-01 11:30:00',
        ];

        // First assignment request completes successfully
        $response1 = $this->actingAs($this->adminUser)
            ->postJson("/api/torneos/encuentros/{$this->encuentro->id_encuentro}/asignar", $payload);

        $response1->assertStatus(200);

        // Immediate second assignment request for the same space and time must fail with 409 Conflict
        $response2 = $this->actingAs($this->adminUser)
            ->postJson("/api/torneos/encuentros/{$encuentro2->id_encuentro}/asignar", $payload);

        $response2->assertStatus(409)
            ->assertJsonFragment(['message' => 'La cancha ya está ocupada por otro encuentro de torneo en ese horario.']);

        // Check database state
        $encuentro1Fresh = $this->encuentro->fresh();
        $encuentro2Fresh = $encuentro2->fresh();

        $this->assertEquals($this->idEspacio, $encuentro1Fresh->id_espacio);
        $this->assertEquals($this->idInstructor, $encuentro1Fresh->id_arbitro_asignado);
        $this->assertNull($encuentro2Fresh->id_arbitro_asignado);
    }

    /**
     * Test: Assigning match fails when referee is already assigned in another space at overlapping time.
     */
    public function test_assign_match_fails_on_referee_collision_different_space()
    {
        // 1. Assign first match
        $this->actingAs($this->adminUser)
            ->postJson("/api/torneos/encuentros/{$this->encuentro->id_encuentro}/asignar", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio,
                'fecha_hora_inicio' => '2026-06-01 10:00:00',
                'fecha_hora_fin' => '2026-06-01 11:30:00',
            ])->assertStatus(200);

        // 2. Create second match
        $encuentro2 = EncuentrosTorneo::create([
            'id_torneo' => $this->torneo->id_torneo,
            'fase_bracket' => '16VOS',
            'numero_encuentro' => 2,
            'estatus_encuentro' => 'PENDIENTE',
        ]);

        // 3. Try to assign second match to SAME referee at DIFFERENT space with overlapping time
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/torneos/encuentros/{$encuentro2->id_encuentro}/asignar", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio2,
                'fecha_hora_inicio' => '2026-06-01 10:30:00', // Overlaps
                'fecha_hora_fin' => '2026-06-01 12:00:00',
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'El árbitro ya tiene un encuentro asignado en ese horario']);
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

    /**
     * Test: Assigning FINAL automatically updates torneo fecha_fin.
     */
    public function test_assign_final_updates_torneo_fecha_fin()
    {
        $finalEncuentro = EncuentrosTorneo::create([
            'id_torneo' => $this->torneo->id_torneo,
            'fase_bracket' => 'FINAL',
            'numero_encuentro' => 15,
            'estatus_encuentro' => 'PENDIENTE',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/torneos/encuentros/{$finalEncuentro->id_encuentro}/asignar", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio,
                'fecha_hora_inicio' => '2026-06-20 18:00:00',
                'fecha_hora_fin' => '2026-06-20 20:00:00',
            ]);

        $response->assertStatus(200);

        $this->assertEquals('2026-06-20', Carbon::parse($this->torneo->fresh()->fecha_fin)->toDateString());
    }

    /**
     * Test: 404 when encounter does not exist.
     */
    public function test_assign_404_when_encuentro_not_found()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/torneos/encuentros/999999/asignar", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio,
                'fecha_hora_inicio' => '2026-06-01 10:00:00',
                'fecha_hora_fin' => '2026-06-01 11:30:00',
            ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['message' => 'Encuentro no encontrado']);
    }

    /**
     * Test: 422 when referee does not belong to the tournament discipline.
     */
    public function test_assign_422_when_referee_discipline_invalid()
    {
        // Delete instructor association with discipline
        DB::table('instructor_disciplina')
            ->where('id_instructor', $this->idInstructor)
            ->where('id_disciplina', $this->idDisciplina)
            ->delete();

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/torneos/encuentros/{$this->encuentro->id_encuentro}/asignar", [
                'id_arbitro' => $this->idInstructor,
                'id_espacio' => $this->idEspacio,
                'fecha_hora_inicio' => '2026-06-01 10:00:00',
                'fecha_hora_fin' => '2026-06-01 11:30:00',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'El árbitro no pertenece a la disciplina del torneo']);
    }
}
