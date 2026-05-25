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

class InscripcionCancelacionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $socioUser;
    protected SocioTitular $socio;
    protected ActividadPlantilla $actividadCerrada;
    protected ActividadPlantilla $actividadAbierta;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Crear Socio y Usuario correspondientes
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
     * Test: Cancelación con > 2 horas de antelación
     * Esperado: Estado cambia a CANCELADA y no hay penalización (No Show no se incrementa)
     */
    public function test_cancelacion_a_tiempo_sin_penalizacion()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 24, 15, 0, 0, 'America/Mexico_City'));

        $sesion = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadCerrada->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-24',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 1,
        ]);

        $inscripcion = InscripcionClase::create([
            'id_sesion' => $sesion->id_sesion,
            'id_usuario' => $this->socio->id_socio,
            'tipo_usuario' => 'socio_titular',
            'fecha_transaccion' => now('America/Mexico_City')->toDateTimeString(),
            'estatus_inscripcion' => 'CONFIRMADA',
        ]);

        $response = $this->actingAs($this->socioUser)
            ->deleteJson("/api/v1/actividades/inscripciones/{$inscripcion->id_inscripcion}");

        $response->assertStatus(200)
            ->assertJson([
                'nuevo_estatus' => 'CANCELADA',
                'penalizacion' => false,
            ]);

        $this->assertEquals('CANCELADA', $inscripcion->fresh()->estatus_inscripcion);
        $this->assertEquals(0, $this->socio->fresh()->contador_no_shows);
        
        Carbon::setTestNow();
    }

    /**
     * Test: Cancelación con < 2 horas de antelación en actividad CERRADA
     * Esperado: Estado cambia a NO_SHOW, incrementa contador no_shows
     */
    public function test_cancelacion_tardia_en_actividad_cerrada_aplica_no_show()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 24, 17, 30, 0, 'America/Mexico_City'));

        $sesion = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadCerrada->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-24',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 1,
        ]);

        $inscripcion = InscripcionClase::create([
            'id_sesion' => $sesion->id_sesion,
            'id_usuario' => $this->socio->id_socio,
            'tipo_usuario' => 'socio_titular',
            'fecha_transaccion' => now('America/Mexico_City')->toDateTimeString(),
            'estatus_inscripcion' => 'CONFIRMADA',
        ]);

        $response = $this->actingAs($this->socioUser)
            ->deleteJson("/api/v1/actividades/inscripciones/{$inscripcion->id_inscripcion}");

        $response->assertStatus(200)
            ->assertJson([
                'nuevo_estatus' => 'FALTA',
                'penalizacion' => true,
            ]);

        $this->assertEquals('FALTA', $inscripcion->fresh()->estatus_inscripcion);
        $this->assertEquals(1, $this->socio->fresh()->contador_no_shows);

        Carbon::setTestNow();
    }

    /**
     * Test: Cancelación con < 2 horas de antelación en actividad ABIERTA
     * Esperado: Estado cambia a CANCELADA sin penalización (ya que es abierta)
     */
    public function test_cancelacion_tardia_en_actividad_abierta_sin_penalizacion()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 24, 17, 30, 0, 'America/Mexico_City'));

        $sesion = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadAbierta->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-24',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 1,
        ]);

        $inscripcion = InscripcionClase::create([
            'id_sesion' => $sesion->id_sesion,
            'id_usuario' => $this->socio->id_socio,
            'tipo_usuario' => 'socio_titular',
            'fecha_transaccion' => now('America/Mexico_City')->toDateTimeString(),
            'estatus_inscripcion' => 'CONFIRMADA',
        ]);

        $response = $this->actingAs($this->socioUser)
            ->deleteJson("/api/v1/actividades/inscripciones/{$inscripcion->id_inscripcion}");

        $response->assertStatus(200)
            ->assertJson([
                'nuevo_estatus' => 'CANCELADA',
                'penalizacion' => false,
            ]);

        $this->assertEquals('CANCELADA', $inscripcion->fresh()->estatus_inscripcion);
        $this->assertEquals(0, $this->socio->fresh()->contador_no_shows);

        Carbon::setTestNow();
    }

    /**
     * Test: Cancelación de invitado con < 2 horas de antelación
     * Esperado: Estado cambia a CANCELADA sin penalización (los invitados no penalizan)
     */
    public function test_cancelacion_tardia_invitado_sin_penalizacion()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 24, 17, 30, 0, 'America/Mexico_City'));

        $sesion = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadCerrada->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-24',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 1,
        ]);

        // Crear Invitado y su Pase
        $idInvitado = \Illuminate\Support\Facades\DB::table('invitados')->insertGetId([
            'socio_id' => $this->socio->id_socio,
            'nombre_invitado' => 'Invitado Prueba',
            'codigo_qr' => 'GUEST_QR',
            'correo' => 'guest@test.com',
            'telefono' => '1234567890',
        ], 'id_invitado');

        $pase = PasesDiarios::create([
            'invitado_id' => $idInvitado,
            'estatus_acceso' => 'ACTIVO',
            'fecha_activacion' => now(),
        ]);

        $inscripcion = InscripcionClase::create([
            'id_sesion' => $sesion->id_sesion,
            'id_usuario' => -$pase->id_pase,
            'tipo_usuario' => 'socio_titular',
            'fecha_transaccion' => now('America/Mexico_City')->toDateTimeString(),
            'estatus_inscripcion' => 'CONFIRMADA',
        ]);

        $response = $this->actingAs($this->socioUser)
            ->deleteJson("/api/v1/actividades/inscripciones/{$inscripcion->id_inscripcion}");

        $response->assertStatus(200)
            ->assertJson([
                'nuevo_estatus' => 'CANCELADA',
                'penalizacion' => false,
            ]);

        $this->assertEquals('CANCELADA', $inscripcion->fresh()->estatus_inscripcion);
        $this->assertEquals(0, $this->socio->fresh()->contador_no_shows);

        Carbon::setTestNow();
    }

    /**
     * Test: Cancelación tardía de miembro familiar aplica el no-show al socio titular
     * Esperado: Estado cambia a FALTA, el contador de no-shows del miembro familiar no cambia (permanece en 0),
     * y el contador de no-shows del socio titular se incrementa en 1.
     */
    public function test_cancelacion_tardia_miembro_familiar_aplica_no_show_a_socio()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 24, 17, 30, 0, 'America/Mexico_City'));

        $sesion = SesionActiva::create([
            'id_actividad_plantilla' => $this->actividadCerrada->id_actividad_plantilla,
            'fecha_sesion' => '2026-05-24',
            'estatus_sesion' => 'DISPONIBLE',
            'cantidad_inscritos' => 1,
        ]);

        $familiar = MiembrosFamiliares::create([
            'socio_id' => $this->socio->id_socio,
            'nombre_completo' => 'Familiar Prueba Penalizacion',
            'parentesco' => 'HIJO/A',
            'genero' => 'M',
            'fecha_nacimiento' => '2010-01-01',
            'correo' => 'fampruebapen@test.com',
            'contador_no_shows' => 0,
        ]);

        $familiarUser = User::create([
            'email' => 'fampruebapen@test.com',
            'password' => bcrypt('password'),
            'rol' => 'miembro_familiar',
            'user_id' => $familiar->id_miembro,
            'activo' => true,
        ]);

        $inscripcion = InscripcionClase::create([
            'id_sesion' => $sesion->id_sesion,
            'id_usuario' => $familiar->id_miembro, // Se registra a nombre del familiar
            'id_miembro_familiar' => $familiar->id_miembro,
            'tipo_usuario' => 'miembro_familiar',
            'fecha_transaccion' => now('America/Mexico_City')->toDateTimeString(),
            'estatus_inscripcion' => 'CONFIRMADA',
        ]);

        $response = $this->actingAs($familiarUser)
            ->deleteJson("/api/v1/actividades/inscripciones/{$inscripcion->id_inscripcion}");

        $response->assertStatus(200)
            ->assertJson([
                'nuevo_estatus' => 'FALTA',
                'penalizacion' => true,
            ]);

        $this->assertEquals('FALTA', $inscripcion->fresh()->estatus_inscripcion);
        // El familiar NO debe incrementar su contador
        $this->assertEquals(0, $familiar->fresh()->contador_no_shows);
        // El socio titular SI debe incrementar su contador
        $this->assertEquals(1, $this->socio->fresh()->contador_no_shows);

        Carbon::setTestNow();
    }
}
