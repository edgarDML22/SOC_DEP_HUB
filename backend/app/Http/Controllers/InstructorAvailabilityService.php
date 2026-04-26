<?php

namespace App\Http\Controllers;

use App\Models\ActividadPlantilla;
use App\Models\TurnosLudoteca;
use Carbon\Carbon;
use App\Models\EncuentrosTorneo;

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
}