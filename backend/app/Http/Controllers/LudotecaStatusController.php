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
            $socio = SocioTitular::where('correo_electronico', $request->correo)->first();
            if ($socio) {
                $id_socio = $socio->id_socio;
            }
        }

        if (!$id_socio) {
            return response()->json([
                'message' => 'No se pudo identificar al socio. Por favor verifique el correo o el ID.',
                'errors' => ['id_socio' => ['El socio es requerido.']]
            ], 422);
        }

        $request->merge(['id_socio' => $id_socio]);

        // VALIDACION 1: MODALIDAD DE PLAN DEL SOCIO
        $modalidad_plan = SocioTitular::where('id_socio', $id_socio)->first();
        if ($modalidad_plan->modalidad_plan != 'FAMILIAR') {
            return response()->json([
                'message' => 'Actualice a plan familiar para usar este servicio',
                'error' => 'PLAN INCORRECTO'
            ], 403);
        }

        //VALIDACION 2: MENOR YA NO SE ENCUENTRA DENTRO DE LA LUDOTECA
        $id_menor = RegistrosLudoteca::where('id_registro', $request->id_registro)
            ->value('id_menor');

        $alreadyActiveToday = RegistrosLudoteca::where('id_menor', $id_menor)
            ->whereDate('hora_ingreso', today())
            ->where('estatus_ludoteca', 'ACTIVA')
            ->exists();

        if ($alreadyActiveToday) {
            return response()->json([
                'message' => 'El menor ya tiene una estancia activa hoy'
            ], 409);
        }
        // registro ya usado 
        $alreadyUsedToday = RegistrosLudoteca::where('id_menor', $id_menor)
            ->whereDate('hora_ingreso', today())
            ->exists();

        if ($alreadyUsedToday) {
            return response()->json([
                'message' => 'El menor ya tiene una estancia usada hoy'
            ], 409);
        }

        //Si el check_in es IN
        $id_menor = RegistrosLudoteca::where('id_registro', $request->id_registro)->value('id_menor');

        $familiar = MiembrosFamiliares::where('id_miembro', $id_menor)
            ->where('socio_id', $request->id_socio)
            ->first();
        /* return response()->json([
            'familiar' => $familiar
        ]); */
        if ($familiar == null) {
            return response()->json([
                'message' => 'Familiar no encontrado',
            ]);
        }
        $registro = RegistrosLudoteca::where('id_registro', $request->id_registro)->update([
            'estatus_ludoteca' => 'ACTIVA',
            'hora_ingreso' => now(),
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

        $id_menor = RegistrosLudoteca::where('id_registro', $request->id_registro)->value('id_menor');

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
                    'id_adulto_ingreso' => null,
                    'id_adulto_egreso' => null,
                    'id_instructor_ingreso' => null,
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
            // ... (existing code for ENTREGADO)
            $estatusFinal = 'COMPLETADA_A_TIEMPO';

            $time = now();
            $limite = RegistrosLudoteca::where('id_registro', $id)
                ->value('hora_limite');

            if ($time > $limite) {
                $estatusFinal = 'COMPLETADA_CON_RETRASO';

                /* SocioTitular::where(
                    'id_socio',
                    $request->id_socio
                )->increment('retrasos_ludoteca', 1); */
                $socio = SocioTitular::find($request->id_socio);

                // aumentar retrasos existentes
                $socio->increment('retrasos_ludoteca');

                $socio->refresh();

                $retrasos = $socio->retrasos_ludoteca;

                if ($retrasos == 3) {
                    $socio->notify(new AlertaRecogidaNotification(null, 'advertencia'));


                } elseif ($retrasos == 5) {

                    $socio->update([
                        'estatus_acceso' => 'SUSPENSION_TEMPORAL',
                        'fecha_fin_suspension' => now()->addDay()
                    ]);

                } elseif ($retrasos == 7) {

                    $socio->update([
                        'estatus_acceso' => 'SUSPENSION_TEMPORAL',
                        'fecha_fin_suspension' => now()->addDays(3)
                    ]);

                } elseif ($retrasos == 9) {

                    $socio->update([
                        'estatus_acceso' => 'SUSPENSION_TEMPORAL',
                        'fecha_fin_suspension' => now()->addDays(5)
                    ]);

                } elseif ($retrasos >= 12) {

                    $socio->update([
                        'estatus_acceso' => 'CANCELADO',
                        'fecha_fin_suspension' => null
                    ]);
                }
            }
            try {
                RegistroLudotecaMongo::insert([
                    'tutor_id' => $request->id_socio,
                    'menor_id' => RegistrosLudoteca::where('id_registro', $id)->value('id_menor'),
                    'hora_ingreso' => RegistrosLudoteca::where('id_registro', $id)->value('hora_ingreso'),
                    'hora_egreso' => now('America/Mexico_City'),
                    'instructor_ingreso' => RegistrosLudoteca::where('id_registro', $id)->value('id_instructor_ingreso'),
                    'instructor_egreso' => $request->id_instructor,
                    'metadata' => [
                        'id_registro' => $id,
                        'estatus_final' => $estatusFinal
                    ]
                ]);
            } catch (\Exception $e) {
                \Log::error('Mongo falló: ' . $e->getMessage());
            }

            // esto corre para ambos casos
            RegistrosLudoteca::where('id_registro', $id)->update([
                'estatus_ludoteca' => $estatusFinal,
                'hora_egreso' => now(),
                'id_adulto_egreso' => $request->id_socio,
                'id_instructor_egreso' => $request->id_instructor,
            ]);


            // enviar encuesta automática
            $socio = SocioTitular::find($request->id_socio);

            $socio->notify(
                new EncuestaLudotecaNotification($id)
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