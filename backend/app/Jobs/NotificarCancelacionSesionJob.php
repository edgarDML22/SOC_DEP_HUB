<?php

namespace App\Jobs;

use App\Mail\SesionCanceladaInstructorMail;
use App\Mail\SesionCanceladaMail;
use App\Models\SesionActiva;
use App\Notifications\SesionCanceladaInstructorNotification;
use App\Notifications\SesionCanceladaSocioNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificarCancelacionSesionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 600];

    public function __construct(protected int $idSesion) {}

    public function handle(): void
    {
        Log::info("NotificarCancelacionSesionJob: iniciando para sesión {$this->idSesion}");

        // hora_inicio, hora_fin, id_disciplina, id_espacio, id_instructor — snapshot.
        // Solo se eager-load las relaciones de display (nombre de disciplina, espacio, instructor).
        $sesion = SesionActiva::with([
            'disciplina:id_disciplina,nombre_disciplina',
            'espacio:id_espacio,nombre_espacio',
            'instructorPrincipal:id_instructor,nombre_completo,correo_electronico',
            'instructorPrincipal.usuario',
        ])->withoutGlobalScopes()->findOrFail($this->idSesion);

        $inscritos = DB::select("
            SELECT
                ic.id_usuario,
                ic.tipo_usuario::text,
                st.id_socio,
                st.nombre_completo    AS nombre_socio,
                st.correo_electronico AS correo_socio,
                mf.correo             AS correo_mf,
                mf.nombre_completo    AS nombre_mf,
                inv.correo            AS correo_inv,
                inv.nombre_invitado   AS nombre_inv
            FROM inscripciones_clases AS ic
            LEFT JOIN socios_titulares AS st
                ON ic.id_usuario = st.id_socio
               AND ic.tipo_usuario::text ILIKE 'socio_titular'
            LEFT JOIN miembros_familiares AS mf
                ON ic.id_usuario = mf.id_miembro
               AND ic.tipo_usuario::text ILIKE 'miembro_familiar'
            LEFT JOIN invitados AS inv
                ON ic.id_usuario = inv.id_invitado
               AND ic.tipo_usuario::text ILIKE 'invitado'
            WHERE ic.id_sesion = ?
              AND lower(ic.estatus_inscripcion::text) != 'cancelada'
        ", [$this->idSesion]);

        $sociosNotificados = [];
        $correosEnviados   = 0;

        Log::info("NotificarCancelacionSesionJob: inscritos encontrados = " . count($inscritos), [
            'detalle' => array_map(fn($i) => [
                'tipo'     => $i->tipo_usuario,
                'id_usuario' => $i->id_usuario,
                'id_socio' => $i->id_socio,
            ], $inscritos),
        ]);

        foreach ($inscritos as $inscrito) {
            // ── Notificación in-app + correo al Socio Titular ─────────────
            if (
                strtoupper($inscrito->tipo_usuario) === 'SOCIO_TITULAR' &&
                $inscrito->id_socio &&
                !in_array($inscrito->id_socio, $sociosNotificados)
            ) {
                $socio = \App\Models\SocioTitular::find($inscrito->id_socio);
                if ($socio) {
                    $socio->notifyNow(new SesionCanceladaSocioNotification(
                        idSesion:    $sesion->id_sesion,
                        fechaSesion: $sesion->fecha_sesion,
                        disciplina:  $sesion->disciplina?->nombre_disciplina,
                        instructor:  $sesion->instructorPrincipal?->nombre_completo,
                        espacio:     $sesion->espacio?->nombre_espacio,
                        horaInicio:  $sesion->hora_inicio,
                        horaFin:     $sesion->hora_fin,
                    ));
                    $sociosNotificados[] = $inscrito->id_socio;
                }

                // Correo al socio titular
                if (!empty($inscrito->correo_socio)) {
                    Mail::to($inscrito->correo_socio)
                        ->send(new SesionCanceladaMail($sesion, $inscrito->nombre_socio));
                    $correosEnviados++;
                }
            }

            // ── Correo a invitados / familiares con correo registrado ─────
            $correo = null;
            $nombre = null;

            if (strtoupper($inscrito->tipo_usuario) === 'INVITADO' && !empty($inscrito->correo_inv)) {
                $correo = $inscrito->correo_inv;
                $nombre = $inscrito->nombre_inv;
            } elseif (strtoupper($inscrito->tipo_usuario) === 'MIEMBRO_FAMILIAR' && !empty($inscrito->correo_mf)) {
                $correo = $inscrito->correo_mf;
                $nombre = $inscrito->nombre_mf;
            }

            if ($correo) {
                Mail::to($correo)->send(new SesionCanceladaMail($sesion, $nombre));
                $correosEnviados++;
            }
        }

        // ── Notificación in-app al Usuario del Instructor ─────────────────
        $instructor        = $sesion->instructorPrincipal;
        $usuarioInstructor = $instructor?->usuario;

        if ($usuarioInstructor) {
            $usuarioInstructor->notifyNow(new SesionCanceladaInstructorNotification(
                idSesion:    $sesion->id_sesion,
                fechaSesion: $sesion->fecha_sesion,
                disciplina:  $sesion->disciplina?->nombre_disciplina,
                espacio:     $sesion->espacio?->nombre_espacio,
                horaInicio:  $sesion->hora_inicio,
                horaFin:     $sesion->hora_fin,
            ));
        }

        // ── Correo al instructor (usa su correo directo de la tabla instructores) ──
        if ($instructor && !empty($instructor->correo_electronico)) {
            Mail::to($instructor->correo_electronico)
                ->send(new SesionCanceladaInstructorMail($sesion));
            $correosEnviados++;
        }

        Log::info("NotificarCancelacionSesionJob: finalizado para sesión {$this->idSesion}", [
            'socios_notificados'    => count($sociosNotificados),
            'correos_enviados'      => $correosEnviados,
            'instructor_notificado' => (bool) $usuarioInstructor,
            'instructor_correo'     => !empty($instructor?->correo_electronico),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("NotificarCancelacionSesionJob: error en sesión {$this->idSesion}: {$e->getMessage()}");
    }
}
