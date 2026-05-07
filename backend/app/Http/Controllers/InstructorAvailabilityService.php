<?php

namespace App\Http\Controllers;

use App\Models\ActividadPlantilla;
use App\Models\RegistrosLudoteca;
use App\Models\torneos;
use App\Models\TurnosLudoteca;
use Carbon\Carbon;
use App\Models\EncuentrosTorneo;
use App\Models\SesionActiva;

class InstructorAvailabilityService
{
    public static function validarDisponibilidad($id_instructor, $fecha, $hora_inicio, $hora_fin)
    {
        $diaSemana = strtoupper(Carbon::parse($fecha)->locale('es')->dayName);
        //revisa que no este empalmado con algun turno de ludoteca
        $already_avaible = TurnosLudoteca::where("id_instructor", $id_instructor)
            ->where("fecha", $fecha)
            ->where("hora_inicio", "<", $hora_fin)
            ->where("hora_fin", ">", $hora_inicio)
            ->first();
        if ($already_avaible) {
            return true;
        }
        //revisa que no este empalmado con alguna actividad
        $activity = ActividadPlantilla::where('id_instructor', $id_instructor)
            ->where('dia_semana', $diaSemana)
            ->where('hora_inicio', '<', $hora_fin)
            ->where('hora_fin', '>', $hora_inicio)
            ->first();

        if ($activity) {
            return true;
        }
        $inicioCompleto = $fecha . ' ' . $hora_inicio;
        $finCompleto = $fecha . ' ' . $hora_fin;
        //revisa que no esten empalmados con algun torneo
        $torneos = EncuentrosTorneo::where('id_arbitro_asignado', $id_instructor)
            ->where('fecha_hora_inicio', '<', $finCompleto)
            ->where('fecha_hora_fin', '>', $inicioCompleto)
            ->first();

        if ($torneos) {
            return true;
        }
        return false;
    }
    public static function getRelationship($id_instructor, $id_disciplina)
    {
        //revisa que no este activo en algun turno de ludoteca
        $already_avaible = null;
        $registro_ludoteca = null;
        
        if ($id_disciplina == 26) {
            $already_avaible = TurnosLudoteca::where("id_instructor", $id_instructor)->first();
            $registro_ludoteca = RegistrosLudoteca::where("id_instructor_ingreso", $id_instructor)->first();
        }

        //revisa que no este activo en alguna actividad
        $activity = ActividadPlantilla::with('disciplina')
            ->where("id_disciplina", $id_disciplina)
            ->where('id_instructor', $id_instructor)
            ->get();
        //revisa todas las actividades del instructor
        $activity_block = ActividadPlantilla::with('disciplina')
            ->where('id_instructor', $id_instructor)
            ->get();

        //revisa que no este activo en algun torneo
        $torneoEncuentro = EncuentrosTorneo::where('id_arbitro_asignado', $id_instructor)
            ->first();

        $torneos = null;
        if ($torneoEncuentro) {
            $torneos = torneos::where('id_torneo', $torneoEncuentro->id_torneo)
                ->where('id_disciplina', $id_disciplina)
                ->first();
        }

        if ($torneos || $activity->isNotEmpty() || $already_avaible || $registro_ludoteca) {
            $response = [
                'success' => true,
                'message' => 'No se puede eliminar la disciplina del instructor',
                'detalles' => [
                    'torneo' => $torneos
                        ? 'El instructor está asignado como árbitro en un torneo con esta disciplina'
                        : null,

                    'actividad' => $activity->isNotEmpty()
                        ? 'El instructor tiene actividades activas con esta disciplina'
                        : null,

                    'ludoteca' => $already_avaible
                        ? 'El instructor tiene un turno activo en ludoteca'
                        : null,

                    'registro_ludoteca' => $registro_ludoteca
                        ? 'El instructor tiene registros en ludoteca'
                        : null,
                ],

                'disciplinas_bloqueadas' => $activity_block
                    ->pluck('disciplina.nombre_disciplina')
                    ->unique()
                    ->values()
                    ->toArray(),
            ];
            return $response;
        }
        return [
            'success' => false
        ];
    }
}