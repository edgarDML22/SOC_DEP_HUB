<?php

namespace App\Services;

use App\Exceptions\ConflictoSemanaException;
use App\Exceptions\DespublicacionBloqueadaException;
use App\Models\ActividadPlantilla;
use App\Models\PlantillaProgramacion;
use App\Models\SesionActiva;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PublicarProgramacionService
{
    private static ?bool $columnaPublicadaEnCache = null;

    /** Mapa de dia_semana (ENUM) → número ISO (1 = lunes … 7 = domingo). */
    private const DIA_ISO = [
        'LUNES'     => 1,
        'MARTES'    => 2,
        'MIERCOLES' => 3,
        'JUEVES'    => 4,
        'VIERNES'   => 5,
        'SABADO'    => 6,
        'DOMINGO'   => 7,
    ];

    // -------------------------------------------------------------------------
    // API pública
    // -------------------------------------------------------------------------

    /**
     * Orquesta la publicación completa dentro de una transacción atómica.
     *
     * Orden de operaciones:
     *   1. Calcula el rango lunes–domingo de la semana indicada.
     *   2. Verifica que no existan sesiones ya publicadas en ese rango (bloqueo estricto).
     *   3. Proyecta las filas a insertar en sesiones_activas.
     *   4. Cambia el estatus de la plantilla a ACTIVO.
     *   5. Inserta las sesiones masivamente.
     *
     * Si cualquier paso falla, la transacción revierte automáticamente.
     *
     * @throws ConflictoSemanaException  Si ya existen sesiones en la semana objetivo.
     * @throws \Throwable                Cualquier error de base de datos.
     *
     * @return array{
     *   id_plantilla: int,
     *   semana_inicio: string,
     *   semana_fin: string,
     *   sesiones_creadas: int
     * }
     */
    public function publicar(int $idPlantilla, string $semanaInicio): array
    {
        $rango = $this->calcularRangoSemana($semanaInicio);

        return DB::transaction(function () use ($idPlantilla, $rango): array {

            $this->verificarConflictoDeSemana($rango);

            $plantilla = PlantillaProgramacion::withoutGlobalScopes()
                ->with(['actividades' => fn($q) => $q->where('estatus', 'ACTIVO')])
                ->findOrFail($idPlantilla);

            $rows = $this->proyectarSesiones($plantilla->actividades, $rango);

            $lunes   = $rango['lunes']->toDateString();
            $domingo = $rango['domingo']->toDateString();

            $plantilla->update([
                'estatus_plantilla' => 'ACTIVO',
                'fecha_inicio'      => $lunes,
                'fecha_fin'         => $domingo,
            ]);

            if (!empty($rows)) {
                SesionActiva::insert($rows);
            }

            return [
                'id_plantilla'     => $plantilla->id_plantilla,
                'semana_inicio'    => $lunes,
                'semana_fin'       => $domingo,
                'sesiones_creadas' => count($rows),
            ];
        });
    }

    /**
     * Calcula el lunes y domingo de la semana que contiene $semanaInicio.
     * Como el Request garantiza que $semanaInicio ya es lunes, el cálculo
     * es determinista: lunes = $semanaInicio, domingo = $semanaInicio + 6 días.
     *
     * @return array{lunes: Carbon, domingo: Carbon}
     */
    public function calcularRangoSemana(string $semanaInicio): array
    {
        $lunes   = Carbon::createFromFormat('Y-m-d', $semanaInicio)->startOfDay();
        $domingo = $lunes->copy()->addDays(6)->endOfDay();

        return ['lunes' => $lunes, 'domingo' => $domingo];
    }

    /**
     * Verifica que no existan sesiones publicadas en el rango lunes–domingo.
     * Usa una sola consulta EXISTS optimizada; no trae filas al PHP.
     *
     * @param  array{lunes: Carbon, domingo: Carbon} $rango
     * @throws ConflictoSemanaException
     */
    public function verificarConflictoDeSemana(array $rango): void
    {
        $total = SesionActiva::withoutGlobalScopes()
            ->whereBetween('fecha_sesion', [
                $rango['lunes']->toDateString(),
                $rango['domingo']->toDateString(),
            ])
            ->count();

        if ($total > 0) {
            throw new ConflictoSemanaException($rango['lunes'], $rango['domingo'], $total);
        }
    }

    /**
     * Proyecta las actividades de una plantilla en filas concretas para sesiones_activas,
     * calculando la fecha real (Y-m-d) de cada sesión según el dia_semana y la semana objetivo.
     *
     * Solo genera sesiones para los días que tienen actividades activas; no itera
     * todos los días de la semana — complejidad O(n) sobre las actividades.
     *
     * @param  Collection<int, ActividadPlantilla>  $actividades  Actividades ACTIVO de la plantilla.
     * @param  array{lunes: Carbon, domingo: Carbon} $rango
     * @return list<array{
     *   id_actividad_plantilla: int,
     *   fecha_sesion: string,
     *   estatus_sesion: string,
     *   cantidad_inscritos: int
     * }>
     */
    public function proyectarSesiones(Collection $actividades, array $rango): array
    {
        $lunes = $rango['lunes'];
        $rows  = [];

        foreach ($actividades as $actividad) {
            $diaISO = self::DIA_ISO[$actividad->dia_semana] ?? null;

            if ($diaISO === null) {
                continue;
            }

            // El lunes es ISO 1; sumar (diaISO - 1) días da la fecha exacta del día en esa semana.
            $fechaSesion = $lunes->copy()->addDays($diaISO - 1)->toDateString();

            $row = [
                'id_actividad_plantilla' => $actividad->id_actividad_plantilla,
                'fecha_sesion'           => $fechaSesion,
                'estatus_sesion'         => 'DISPONIBLE',
                'cantidad_inscritos'     => 0,
            ];

            // Incluir fecha_publicacion solo si la columna ya existe en la BD.
            if ($this->columnaPublicadaEnExiste()) {
                $row['fecha_publicacion'] = now();
            }

            $rows[] = $row;
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Despublicación
    // -------------------------------------------------------------------------

    /**
     * Elimina todas las sesiones de una plantilla y resetea sus fechas.
     *
     * Regla de tiempo (requiere columna fecha_publicacion en sesiones_activas):
     *   – Si la publicación más antigua tiene menos de 30 minutos → hard delete sin restricciones.
     *   – Si ya pasaron 30 minutos → bloquear si hay inscripciones o sesiones COMPLETADA.
     *     Si no hay ninguna de las dos condiciones → hard delete igualmente.
     *
     * Si la columna fecha_publicacion no existe aún en la BD, siempre se aplica la
     * verificación de bloqueo (comportamiento conservador hasta que se ejecute la migración).
     *
     * La plantilla queda con estatus INACTIVO y fecha_inicio / fecha_fin en null.
     *
     * @throws DespublicacionBloqueadaException  Si aplica el bloqueo por actividad registrada.
     * @throws \Throwable                        Cualquier error de base de datos.
     *
     * @return array{sesiones_eliminadas: int}
     */
    public function despublicar(int $idPlantilla): array
    {
        return DB::transaction(function () use ($idPlantilla): array {

            $plantilla = PlantillaProgramacion::withoutGlobalScopes()->findOrFail($idPlantilla);

            $idActividades = $plantilla->actividades()->pluck('id_actividad_plantilla');

            if ($idActividades->isEmpty()) {
                return ['sesiones_eliminadas' => 0];
            }

            $sesiones = SesionActiva::withoutGlobalScopes()
                ->whereIn('id_actividad_plantilla', $idActividades)
                ->get(['id_sesion', 'estatus_sesion', 'cantidad_inscritos']);

            if ($sesiones->isEmpty()) {
                $this->resetearPlantilla($plantilla);
                return ['sesiones_eliminadas' => 0];
            }

            $dentroVentana = $this->verificarVentana30min($idActividades->toArray());

            if (!$dentroVentana) {
                $sesionesConInscritos = $sesiones->where('cantidad_inscritos', '>', 0)->count();
                $sesionesCompletadas  = $sesiones->where('estatus_sesion', 'COMPLETADA')->count();

                if ($sesionesConInscritos > 0 || $sesionesCompletadas > 0) {
                    throw new DespublicacionBloqueadaException($sesionesConInscritos, $sesionesCompletadas);
                }
            }

            $eliminadas = SesionActiva::withoutGlobalScopes()
                ->whereIn('id_sesion', $sesiones->pluck('id_sesion'))
                ->delete();

            $this->resetearPlantilla($plantilla);

            return ['sesiones_eliminadas' => $eliminadas];
        });
    }

    /**
     * Determina si la publicación ocurrió hace menos de 30 minutos.
     *
     * Consulta fecha_publicacion de forma defensiva: si la columna no existe en la BD
     * (migración pendiente), captura la excepción y devuelve false para que
     * siempre se aplique la verificación de bloqueo.
     *
     * @param  int[]  $idActividades
     */
    private function verificarVentana30min(array $idActividades): bool
    {
        try {
            $masAntigua = DB::table('sesiones_activas')
                ->whereIn('id_actividad_plantilla', $idActividades)
                ->whereNotNull('fecha_publicacion')
                ->min('fecha_publicacion');

            if ($masAntigua === null) {
                return false;
            }

            return Carbon::parse($masAntigua)->diffInMinutes(now()) < 30;
        } catch (\Illuminate\Database\QueryException) {
            // La columna fecha_publicacion aún no existe — comportamiento conservador.
            return false;
        }
    }

    private function resetearPlantilla(PlantillaProgramacion $plantilla): void
    {
        $plantilla->update([
            'estatus_plantilla' => 'INACTIVO',
            'fecha_inicio'      => null,
            'fecha_fin'         => null,
        ]);
    }

    /** Verifica una sola vez por proceso si la columna fecha_publicacion ya existe. */
    private function columnaPublicadaEnExiste(): bool
    {
        if (self::$columnaPublicadaEnCache !== null) {
            return self::$columnaPublicadaEnCache;
        }

        $existe = DB::table('information_schema.columns')
            ->where('table_name', 'sesiones_activas')
            ->where('column_name', 'fecha_publicacion')
            ->exists();

        self::$columnaPublicadaEnCache = $existe;

        return $existe;
    }
}
