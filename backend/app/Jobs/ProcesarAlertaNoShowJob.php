<?php

namespace App\Jobs;

use App\Models\InscripcionClase;
use App\Models\MiembrosFamiliares;
use App\Models\RegistroAsistencia;
use App\Models\SesionActiva;
use App\Models\SocioTitular;
use App\Notifications\AlertaNoShowNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcesarAlertaNoShowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    protected int $idInscripcion;

    public function __construct(int|InscripcionClase $inscripcion)
    {
        $this->idInscripcion = $inscripcion instanceof InscripcionClase
            ? $inscripcion->id_inscripcion
            : $inscripcion;
    }

    public function handle(): void
    {
        try {
            $lockKey = "lock_no_show_notif_{$this->idInscripcion}";

            Cache::lock($lockKey, 15)->get(function () {
                $inscripcion = InscripcionClase::find($this->idInscripcion);
                if (!$inscripcion) {
                    Log::warning("ProcesarAlertaNoShowJob: Inscripción #{$this->idInscripcion} no encontrada.");
                    return;
                }

                $tipoUsuario = strtolower((string) $inscripcion->tipo_usuario);
                if ($tipoUsuario === 'invitado' || (int) $inscripcion->id_usuario < 0) {
                    return;
                }

                $socio = $this->resolverSocioResponsable($inscripcion);
                if (!$socio) {
                    Log::warning("ProcesarAlertaNoShowJob: No se pudo resolver socio titular para inscripción #{$this->idInscripcion}.");
                    return;
                }

                $sesion = SesionActiva::withoutGlobalScopes()
                    ->with('disciplina:id_disciplina,nombre_disciplina')
                    ->find($inscripcion->id_sesion);

                if (!$sesion) {
                    Log::warning("ProcesarAlertaNoShowJob: Sesión #{$inscripcion->id_sesion} no encontrada.");
                    return;
                }

                $yaNotificado = DB::table('notifications')
                    ->where('notifiable_type', 'SOCIO')
                    ->where('notifiable_id', $socio->id_socio)
                    ->where('data', 'like', '%"id_sesion":' . $sesion->id_sesion . '%')
                    ->where('data', 'like', '%"tipo":"NO_SHOW_ALERTA"%')
                    ->exists();

                if ($yaNotificado) {
                    Log::info("ProcesarAlertaNoShowJob: Notificación previa existente para socio #{$socio->id_socio} y sesión #{$sesion->id_sesion}.");
                    return;
                }

                $tz = 'America/Mexico_City';
                $inicioMes = Carbon::now($tz)->startOfMonth();
                $finMes = Carbon::now($tz)->endOfMonth();

                $faltasMes = RegistroAsistencia::where('id_usuario', $socio->id_socio)
                    ->where('tipo_usuario', 'SOCIO_TITULAR')
                    ->where('asistencia', false)
                    ->whereBetween('fecha_hora_registro', [$inicioMes, $finMes])
                    ->count();

                $faltasAcumuladas = max($faltasMes, (int) ($socio->contador_no_shows ?? 0));

                $ahora = Carbon::now($tz);
                $tienePenalizacionActiva = (
                    $socio->estatus_penalizacion === 'PENALIZADO'
                    || in_array($socio->estatus_cuenta, ['SUSPENDIDO', 'PENALIZADO'], true)
                    || ($socio->fecha_fin_penalizacion_reserva && Carbon::parse($socio->fecha_fin_penalizacion_reserva, $tz)->isAfter($ahora))
                );

                $fechaFin = $socio->fecha_fin_penalizacion_reserva
                    ? Carbon::parse($socio->fecha_fin_penalizacion_reserva, $tz)->format('Y-m-d H:i')
                    : null;

                $disciplinaNombre = $sesion->disciplina?->nombre_disciplina ?? 'Actividad Programada';
                $fechaSesion = (string) ($sesion->fecha_sesion ?? Carbon::today($tz)->toDateString());
                $horaInicio = (string) ($sesion->hora_inicio ?? '00:00');

                $socio->notify(new AlertaNoShowNotification(
                    idSesion: $sesion->id_sesion,
                    disciplina: $disciplinaNombre,
                    fecha: $fechaSesion,
                    horaInicio: $horaInicio,
                    faltasAcumuladas: $faltasAcumuladas,
                    estaPenalizado: $tienePenalizacionActiva,
                    fechaFinPenalizacion: $fechaFin,
                ));

                Log::info("ProcesarAlertaNoShowJob: Alerta de No-Show emitida para socio #{$socio->id_socio} en sesión #{$sesion->id_sesion}.");
            });
        } catch (\Throwable $e) {
            Log::error("ProcesarAlertaNoShowJob: Error procesando alerta de No-Show para inscripción #{$this->idInscripcion}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function resolverSocioResponsable(InscripcionClase $inscripcion): ?SocioTitular
    {
        $tipo = strtolower((string) $inscripcion->tipo_usuario);

        if ($tipo === 'socio_titular' || $tipo === 'socio') {
            return SocioTitular::find($inscripcion->id_usuario);
        }

        if ($tipo === 'miembro_familiar' || $tipo === 'familiar') {
            $idFamiliar = $inscripcion->id_usuario ?? $inscripcion->id_miembro_familiar;
            $familiar = MiembrosFamiliares::find($idFamiliar);
            if ($familiar && $familiar->socio_id) {
                return SocioTitular::find($familiar->socio_id);
            }
        }

        return null;
    }
}
