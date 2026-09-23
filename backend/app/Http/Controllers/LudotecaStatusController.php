<?php

namespace App\Http\Controllers;

use App\Models\RegistrosLudoteca;
use Illuminate\Http\Request;
use App\Models\SocioTitular;
use App\Models\MiembrosFamiliares;
use App\Models\MongoDB\RegistroLudotecaMongo;
use Illuminate\Support\Facades\DB;
use App\Notifications\AlertaRecogidaNotification;
use App\Notifications\EncuestaLudotecaNotification;
use App\Models\HistorialLudoteca;
use Illuminate\Support\Facades\Auth;

class LudotecaStatusController extends Controller
{
    //Modificado completamente en la SDH-163 por el cambio de la logica de la ludotecaen
    public function checkIn(Request $request)
    {
        // VALIDACION DE CAMPOS REQUERIDOS
        $request->validate([
            'id_socio' => 'nullable|exists:socios_titulares,id_socio',
            'id_registro' => 'required|exists:registros_ludoteca,id_registro',
            'id_instructor' => 'required|exists:instructores,id_instructor',
            'correo' => 'nullable|email'
        ]);

        $id_socio = $request->id_socio;

        // Si no viene id_socio, intentamos buscar por correo (para re-ingresos de inactivos)
        if (!$id_socio && $request->correo) {
            $socioByCorreo = SocioTitular::where('correo_electronico', $request->correo)
                ->value('id_socio');
            if ($socioByCorreo) {
                $id_socio = $socioByCorreo;
            }
        }

        if (!$id_socio) {
            return response()->json([
                'message' => 'No se pudo identificar al socio. Por favor verifique el correo o el ID.',
                'errors' => ['id_socio' => ['El socio es requerido.']]
            ], 422);
        }

        $request->merge(['id_socio' => $id_socio]);

        // Carga única: socio + sus familiares + los registros de hoy de cada familiar.
        // Todas las validaciones siguientes operan en memoria sobre estas colecciones.
        $socio = SocioTitular::with([
            'miembrosFamiliares.registrosLudoteca' => function ($query) {
                $query->whereDate('hora_ingreso', today());
            },
        ])->find($id_socio);

        // VALIDACION 1: MODALIDAD DE PLAN DEL SOCIO
        if ($socio->modalidad_plan != 'FAMILIAR') {
            return response()->json([
                'message' => 'Actualice a plan familiar para usar este servicio',
                'error' => 'PLAN INCORRECTO'
            ], 403);
        }

        $penalizacionLudoteca = in_array($socio->estatus_penalizacion, ['PENALIZADO_LUDOTECA', 'PENALIZADO_AMBOS', 'SUSPENDIDO'])
            && ($socio->estatus_penalizacion === 'SUSPENDIDO'
                || ($socio->fecha_fin_penalizacion_ludoteca && $socio->fecha_fin_penalizacion_ludoteca->isFuture()));

        if ($penalizacionLudoteca) {
            return response()->json([
                'message' => 'Su cuenta tiene una penalización activa en Ludoteca.',
                'error'   => 'PENALIZACION_LUDOTECA',
                'fecha_liberacion' => $socio->fecha_fin_penalizacion_ludoteca?->toDateTimeString(),
            ], 403);
        }

        // Obtenemos el id_menor desde el registro ya validado por la regla exists: de arriba.
        // Esta es la única query adicional necesaria porque el registro no pertenece al socio.
        $id_menor = RegistrosLudoteca::where('id_registro', $request->id_registro)
            ->value('id_menor');

        // VALIDACION 2: verificaciones de estancia en memoria sobre la colección eager-loaded.
        $familiar = $socio->miembrosFamiliares->firstWhere('id_miembro', $id_menor);

        if ($familiar === null) {
            return response()->json([
                'message' => 'Familiar no encontrado',
            ]);
        }

        $registrosHoyDelMenor = $familiar->registrosLudoteca;

        $alreadyActiveToday = $registrosHoyDelMenor->contains('estatus_ludoteca', 'ACTIVA');

        if ($alreadyActiveToday) {
            return response()->json([
                'message' => 'El menor ya tiene una estancia activa hoy'
            ], 409);
        }

        // Si existe cualquier registro de hoy (activo o no), el menor ya usó su entrada.
        $alreadyUsedToday = $registrosHoyDelMenor->isNotEmpty();

        if ($alreadyUsedToday) {
            return response()->json([
                'message' => 'El menor ya tiene una estancia usada hoy'
            ], 409);
        }

        $registro = RegistrosLudoteca::where('id_registro', $request->id_registro)->update([
            'estatus_ludoteca' => 'ACTIVA',
            'hora_ingreso' => now('America/Mexico_City'),
            'id_adulto_ingreso' => $request->id_socio,
            'hora_egreso' => null,
            'id_adulto_egreso' => null,
            'id_instructor_ingreso' => $request->id_instructor,
            'alerta_30_enviada' => false,
            'alerta_10_enviada' => false,
        ]);

        return response()->json([
            'message' => 'Ingreso registrado correctamente',
            'registro' => $registro
        ]);

    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'id_registro' => 'required|exists:registros_ludoteca,id_registro',
            'id_socio' => 'nullable|exists:socios_titulares,id_socio',
            'id_instructor' => 'required|exists:instructores,id_instructor',
            'estatus_ludoteca' => 'required|in:INACTIVO,ENTREGADO,ACTIVA',
            'correo_receptor' => 'nullable|email'
        ]);

        $id_socio = $request->id_socio;

        // Si se proporciona un correo de receptor, buscamos a ese socio para validar la salida
        if ($request->correo_receptor) {
            $socio = SocioTitular::where('correo_electronico', $request->correo_receptor)->first();
            $id_socio = $socio ? $socio->id_socio : null;
        }

        $registro = RegistrosLudoteca::where('id_registro', $request->id_registro)->firstOrFail();
        $id_menor = $registro->id_menor;

        // Validamos que el socio (identificado por ID o Correo) sea familiar del menor
        $familiar = MiembrosFamiliares::where('id_miembro', $id_menor)
            ->where('socio_id', $id_socio)
            ->first();

        if ($familiar == null) {
            return response()->json([
                'success' => false,
                'message' => 'El socio indicado no tiene parentesco con el menor o no existe.',
                'errors' => ['correo_receptor' => ['Socio no autorizado para esta acción.']]
            ], 403);
        }

        // Actualizamos el request para que el resto del código use el socio validado
        $request->merge(['id_socio' => $id_socio]);

        if ($request->estatus_ludoteca == 'INACTIVO') {
            RegistrosLudoteca::whereNotNull('hora_ingreso')
                ->where('id_registro', $request->id_registro)
                ->update([
                    'estatus_ludoteca' => 'INACTIVO',
                    'hora_egreso' => null,
                    'id_adulto_egreso' => null,
                    'id_instructor_egreso' => null,
                    'alerta_30_enviada' => false,
                    'alerta_10_enviada' => false,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Menor marcado como inactivo'
            ]);
        }

        if ($request->estatus_ludoteca == 'ENTREGADO') {
            $estatusFinal = 'COMPLETADA_A_TIEMPO';

            $limite = $registro->hora_limite;
            $time = now('America/Mexico_City');

            // Carga única del socio para todas las operaciones de este bloque.
            $socio = SocioTitular::find($request->id_socio);

            if ($time > $limite) {
                $estatusFinal = 'COMPLETADA_CON_RETRASO';

                $socio->increment('retrasos_ludoteca', 1);
                // Pasamos el id; Sanciones hace su propio find() para operar de forma independiente.
                Sanciones::aplicarSanciones($socio->id_socio);
            }

            $horaIngreso = \Carbon\Carbon::parse($registro->hora_ingreso);
            $horaEgreso = now('America/Mexico_City');

            $tiempoTotal = (int) round(
                $horaIngreso->diffInMinutes($horaEgreso)
            );

            $historial = HistorialLudoteca::create([
                'id_registro_operativo' => $request->id_registro,
                'id_menor' => $registro->id_menor,
                'id_adulto' => $registro->id_adulto_ingreso,
                'tiempo_total_minutos' => $tiempoTotal,
                'id_instructor_ingreso' => $registro->id_instructor_ingreso,
                'id_instructor_egreso' => $registro->id_instructor_ingreso,
                'hora_egreso' => $horaEgreso,
                'hora_ingreso' => $horaIngreso,
                'creado_el' => now('America/Mexico_City'),
                'estatus_final' => $estatusFinal
            ]);

            RegistrosLudoteca::where('id_registro', $request->id_registro)->update([
                'estatus_ludoteca' => $estatusFinal,
                'hora_egreso' => now('America/Mexico_City'),
                'id_adulto_egreso' => $request->id_socio,
                'id_instructor_egreso' => $request->id_instructor,
            ]);

            // Reutilizamos $socio ya cargado arriba — sin segundo find().
            $socio->notify(
                new EncuestaLudotecaNotification($historial->id_historial)
            );

            return response()->json([
                'success' => true,
                'message' => 'Salida registrada correctamente'
            ]);
        }
    }

    public function getChildrenStatus(Request $request)
    {
        $request->validate([
            'id_socio' => 'required|exists:socios_titulares,id_socio',
        ]);
        //REGISTROS DE HOY
        $registros = RegistrosLudoteca::whereDate('hora_ingreso', today())->where('id_adulto_ingreso', $request->id_socio)->get();
        return response()->json([
            'activos' => $registros->where('estatus_ludoteca', 'ACTIVA')->values(),
            'inactivos' => $registros->where('estatus_ludoteca', 'INACTIVO')->values(),

            'completados' => $registros->whereIn('estatus_ludoteca', [
                'COMPLETADA_A_TIEMPO',
                'COMPLETADA_CON_RETRASO'
            ])->values()
        ]);
    }
}