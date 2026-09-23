<?php

namespace Tests\Feature;

use App\Models\ActividadPlantilla;
use App\Models\InscripcionClase;
use App\Models\MiembrosFamiliares;
use App\Models\PasesDiarios;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InscripcionFamiliarInvitadoTest extends TestCase
{
    use DatabaseTransactions;

    protected User $socioUser;
    protected SocioTitular $socio;
    protected ActividadPlantilla $actividadCerrada;
    protected ActividadPlantilla $actividadAbierta;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Crear Socio y Usuario
        $this->socio = SocioTitular::create([
            'nombre_completo' => 'Socio De Prueba',
            'estatus_cuenta' => 'AL_CORRIENTE',
            'contador_no_shows' => 0,
            'correo' => 'socio.prueba@test.com',
            'telefono' => '1234567890',
            'numero_accion' => 'ACCION-9999',
            'tipo_socio' => 'ACCIONISTA',
            'modalidad_plan' => 'INDIVIDUAL',
            'genero' => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'correo_electronico' => 'socio.prueba@test.com',
        ]);

        $this->socioUser = User::create([
            'email' => 'socio.prueba@test.com',
            'password' => bcrypt('password'),
            'rol' => 'socio_titular',
            'user_id' => $this->socio->id_socio,
            'activo' => true,
        ]);

        // 2. Crear Disciplina
        $idDisciplina = \Illuminate\Support\Facades\DB::table('disciplinas')->insertGetId([
            'nombre_disciplina' => 'Yoga Test',
            'estatus' => 'ACTIVO',
        ], 'id_disciplina');

        $idEspacio = \Illuminate\Support\Facades\DB::table('espacios_fisicos')->value('id_espacio');
        $idInstructor = \Illuminate\Support\Facades\DB::table('instructores')->value('id_instructor');

        // 3. Crear Actividades Plantilla
        $this->actividadCerrada = ActividadPlantilla::create([
            'id_disciplina' => $idDisciplina,
            'id_espacio' => $idEspacio,
            'id_instructor' => $idInstructor,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
            'cupo_maximo' => 15,
            'requiere_inscripcion' => true,
            'dia_semana' => 'LUNES',
            'estatus' => 'ACTIVO',
        ]);

        $this->actividadAbierta = ActividadPlantilla::create([
            'id_disciplina' => $idDisciplina,
            'id_espacio' => $idEspacio,
            'id_instructor' => $idInstructor,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
            'cupo_maximo' => 100,
            'requiere_inscripcion' => false,
            'dia_semana' => 'LUNES',
            'estatus' => 'ACTIVO',
        ]);
    }

    /**
     * Test: Inscribir con miembro familiar válido
     */
    public function test_inscribir_miembro_familiar_exito()
    {
        $familiar = MiembrosFamiliares::create([
            'socio_id' => $this->socio->id_socio,
            'nombre_completo' => 'Familiar Uno',
            'parentesco' => 'HIJO/A',
            'genero' => 'M',
            'fecha_nacimiento' => '2010-01-01',
            'correo' => 'fam1@test.com',
        ]);

        $sesion = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadCerrada->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-24',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 0,
        ]);

        $response = $this->actingAs($this->socioUser)
            ->postJson("/api/v1/actividades/sesiones/{$sesion->id_sesion}/inscribir", [
                'id_miembro_familiar' => $familiar->id_miembro,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo_usuario', 'miembro_familiar');

        $this->assertDatabaseHas('inscripciones_clases', [
            'id_sesion' => $sesion->id_sesion,
            'id_usuario' => $familiar->id_miembro,
            'tipo_usuario' => 'miembro_familiar',
        ]);
    }

    /**
     * Test: Inscribir familiar que NO pertenece al socio
     */
    public function test_inscribir_miembro_familiar_no_pertenece_lanza_403()
    {
        // Crear un socio ajeno para cumplir la FK
        $socioAjeno = SocioTitular::create([
            'nombre_completo' => 'Socio Ajeno',
            'estatus_cuenta' => 'AL_CORRIENTE',
            'contador_no_shows' => 0,
            'correo' => 'socio.ajeno@test.com',
            'telefono' => '1234567890',
            'numero_accion' => 'ACCION-8888',
            'tipo_socio' => 'ACCIONISTA',
            'modalidad_plan' => 'INDIVIDUAL',
            'genero' => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'correo_electronico' => 'socio.ajeno@test.com',
        ]);

        // Familiar de otro socio
        $familiarAjeno = MiembrosFamiliares::create([
            'socio_id' => $socioAjeno->id_socio,
            'nombre_completo' => 'Familiar Ajeno',
            'parentesco' => 'HIJO/A',
            'genero' => 'M',
            'fecha_nacimiento' => '2010-01-01',
            'correo' => 'famajeno@test.com',
        ]);

        $sesion = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadCerrada->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-24',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 0,
        ]);

        $response = $this->actingAs($this->socioUser)
            ->postJson("/api/v1/actividades/sesiones/{$sesion->id_sesion}/inscribir", [
                'id_miembro_familiar' => $familiarAjeno->id_miembro,
            ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['message' => 'El miembro familiar no pertenece a tu cuenta.']);
    }

    /**
     * Test: Inscribir invitado con pase activo hoy en sesión abierta/cerrada
     */
    public function test_inscribir_invitado_exito()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 24, 12, 0, 0, 'America/Mexico_City'));

        $sesion = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadCerrada->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-24',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 0,
        ]);

        $idInvitado = \Illuminate\Support\Facades\DB::table('invitados')->insertGetId([
            'socio_id' => $this->socio->id_socio,
            'nombre_invitado' => 'Invitado Uno',
            'codigo_qr' => 'GUEST_QR',
            'correo' => 'guest1@test.com',
            'telefono' => '1234567890',
        ], 'id_invitado');

        $pase = PasesDiarios::create([
            'invitado_id' => $idInvitado,
            'estatus_acceso' => 'ACTIVO',
            'fecha_activacion' => now(),
        ]);

        $response = $this->actingAs($this->socioUser)
            ->postJson("/api/v1/actividades/sesiones/{$sesion->id_sesion}/inscribir", [
                'id_pase_invitado' => $pase->id_pase,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo_usuario', 'invitado');

        $this->assertDatabaseHas('inscripciones_clases', [
            'id_sesion' => $sesion->id_sesion,
            'id_usuario' => -$pase->id_pase,
            'tipo_usuario' => 'socio_titular',
        ]);

        Carbon::setTestNow();
    }

    /**
     * Test: Inscribir invitado con pase expirado
     */
    public function test_inscribir_invitado_pase_expirado_lanza_422()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 24, 12, 0, 0, 'America/Mexico_City'));

        $sesion = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadCerrada->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-24',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 0,
        ]);

        $idInvitado = \Illuminate\Support\Facades\DB::table('invitados')->insertGetId([
            'socio_id' => $this->socio->id_socio,
            'nombre_invitado' => 'Invitado Uno',
            'codigo_qr' => 'GUEST_QR',
            'correo' => 'guest1@test.com',
            'telefono' => '1234567890',
        ], 'id_invitado');

        $paseExpirado = PasesDiarios::create([
            'invitado_id' => $idInvitado,
            'estatus_acceso' => 'EXPIRADO', // Usar EXPIRADO en vez de INACTIVO
            'fecha_activacion' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->socioUser)
            ->postJson("/api/v1/actividades/sesiones/{$sesion->id_sesion}/inscribir", [
                'id_pase_invitado' => $paseExpirado->id_pase,
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'El pase de invitado no está activo o no existe.']);

        Carbon::setTestNow();
    }

    /**
     * Test: Inscribir invitado en sesión futura (no de hoy)
     */
    public function test_inscribir_invitado_en_fecha_futura_lanza_422()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 24, 12, 0, 0, 'America/Mexico_City'));

        $sesionFutura = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadCerrada->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-25', // Mañana
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 0,
        ]);

        $idInvitado = \Illuminate\Support\Facades\DB::table('invitados')->insertGetId([
            'socio_id' => $this->socio->id_socio,
            'nombre_invitado' => 'Invitado Uno',
            'codigo_qr' => 'GUEST_QR',
            'correo' => 'guest1@test.com',
            'telefono' => '1234567890',
        ], 'id_invitado');

        $pase = PasesDiarios::create([
            'invitado_id' => $idInvitado,
            'estatus_acceso' => 'ACTIVO',
            'fecha_activacion' => now(),
        ]);

        $response = $this->actingAs($this->socioUser)
            ->postJson("/api/v1/actividades/sesiones/{$sesionFutura->id_sesion}/inscribir", [
                'id_pase_invitado' => $pase->id_pase,
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Los invitados solo pueden inscribirse en sesiones del día de hoy.']);

        Carbon::setTestNow();
    }
}
