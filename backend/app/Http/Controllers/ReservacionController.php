<?php

namespace App\Http\Controllers;

use App\Models\Disciplina;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use App\Models\EspacioFisico;
use App\Models\Reservacion;
use Illuminate\Support\Facades\Validator;
use App\Models\SesionActiva;

class ReservacionController extends Controller
{
    // 1. CREAR RESERVA (Paso 3 del Front)
    public function store(Request $request)
    {
        try {
            $request->validate([
                'fecha_reserva' => 'required|date|after_or_equal:today',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'id_espacio' => 'required|integer|exists:espacios_fisicos,id_espacio',
                'id_disciplina' => 'required|integer|exists:disciplinas,id_disciplina',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }

        $espacioActivo = EspacioFisico::where('id_espacio', $request->id_espacio)->where('estatus', 'ACTIVO')->exists();
        if (!$espacioActivo) {
            return response()->json(['success' => false, 'message' => 'El espacio no está disponible'], 400);
        }

        $disciplinaActiva = Disciplina::where('id_disciplina', $request->id_disciplina)->exists();
        if (!$disciplinaActiva) {
            return response()->json(['success' => false, 'message' => 'La disciplina no está disponible'], 400);
        }

        $inicio = Carbon::parse($request->hora_inicio);
        $fin = Carbon::parse($request->hora_fin);

        if ($inicio->diffInMinutes($fin) > 120) {
            return response()->json(['success' => false, 'message' => 'La reservación no puede exceder las 2 horas.'], 400);
        }

        return DB::transaction(function () use ($request) {

            $id_socio = $request->user()->user_id;
            // 1. VALIDAR EMPALMES CON OTRAS RESERVACIONES
            // ... dentro del transaction ...
            $conflictoReserva = Reservacion::where('id_espacio', $request->id_espacio)
                ->where('fecha_reserva', $request->fecha_reserva)
                ->where(function ($q) use ($id_socio) { // <-- Pasamos la variable
                    $q->where('estatus_operativo', 'ACTIVA')
                        ->orWhere(function ($sub) use ($id_socio) { // <-- Pasamos la variable
                            $sub->where('estatus_operativo', 'PENDIENTE')
                                ->where('fecha_expiracion', '>', now())
                                ->where('id_socio_titular', '!=', $id_socio); // <-- LA EXCEPCIÓN
                        });
                })
                ->where(function ($query) use ($request) {
                    $query->where('hora_inicio', '<', $request->hora_fin)
                        ->where('hora_fin', '>', $request->hora_inicio);
                })
                ->exists();

            // 2. VALIDAR EMPALMES CON CLASES/SESIONES ACTIVAS
            $conflictoSesion = SesionActiva::where('fecha_sesion', $request->fecha_reserva)
                ->whereNotIn('estatus_sesion', ['CANCELADA', 'FINALIZADA'])
                ->whereHas('actividadPlantilla', function ($query) use ($request) {
                    $query->where('id_espacio', $request->id_espacio)
                        ->where('hora_inicio', '<', $request->hora_fin)
                        ->where('hora_fin', '>', $request->hora_inicio);
                })
                ->exists();

            // ** PENDIENTE ** VALIDAR EMPALMES CON encuentros_torneos
            //meterlo en el OR de aqui abajo

            if ($conflictoReserva || $conflictoSesion) {
                return response()->json(['success' => false, 'message' => 'Espacio agotado. Ya existe una actividad en este horario'], 409);
            }

            // SI TODO ESTÁ LIBRE, CREAMOS LA RESERVA

            $nuevaReserva = Reservacion::updateOrCreate(
                [
                    // 1. CONDICIÓN DE BÚSQUEDA: 
                    // Búscame una reserva de este socio que esté pendiente...
                    'id_socio_titular'  => $id_socio,
                    'estatus_operativo' => 'PENDIENTE'
                ],
                [
                    // 2. VALORES A ACTUALIZAR (o a insertar si es nueva):
                    // Le actualizamos el espacio, la disciplina y las horas nuevas.
                    'id_espacio'       => $request->id_espacio,
                    'id_disciplina'    => $request->id_disciplina,
                    'fecha_reserva'    => $request->fecha_reserva,
                    'hora_inicio'      => $request->hora_inicio,
                    'hora_fin'         => $request->hora_fin,
                    'fecha_expiracion' => Carbon::now()->addMinutes(10),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Reservación agregada correctamente',
                'id_reserva' => $nuevaReserva->id_reserva
            ]);
        });
    }

    public function addAcompanante(Request $request, $id)
    {
        // Validación de datos entrantes (se asume que se recibe acompanante_id)
        $validator = Validator::make($request->all(), [
            'acompanante_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Faltan parámetros requeridos o son inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        $acompananteId = $request->acompanante_id;

        // 1. El usuario (acompañante) debe existir
        // Se valida en la tabla 'users' y/o 'socios_titulares' y 'miembros_familiares'
        $existeEnUsers = DB::table('users')->where('id', $acompananteId)->exists();
        $existeEnSocios = DB::table('socios_titulares')->where('id_socio', $acompananteId)->exists();
        $existeEnFamiliares = DB::table('miembros_familiares')->where('id_miembro', $acompananteId)->exists();

        if (!$existeEnUsers && !$existeEnSocios && !$existeEnFamiliares) {
            return response()->json([
                'success' => false,
                'message' => 'El acompañante especificado no existe.'
            ], 404);
        }

        // Obtener datos de la reservación padre
        $reservacion = DB::table('reservaciones_on_demand')->where('id_reserva', $id)->first();
        if (!$reservacion) {
            return response()->json([
                'success' => false,
                'message' => 'La reservación a la que intentas agregar el acompañante no existe.'
            ], 404);
        }

        // 2. Validación de conflicto de horario
        // Validar que el acompañante no tenga otra reservación activa en el mismo horario
        $tieneConflicto = DB::table('reservaciones_usuarios')
            ->join('reservaciones_on_demand as rod', 'rod.id_reserva', '=', 'reservaciones_usuarios.reservacion_id')
            ->where('reservaciones_usuarios.usuario_id', $acompananteId)
            ->where('rod.fecha_reserva', $reservacion->fecha_reserva)
            ->where('rod.estatus_operativo', '!=', 'CANCELADA')
            ->where(function ($query) use ($reservacion) {
                // Hay conflicto si el inicio de otra es antes del fin, y su fin es después del inicio
                $query->where('rod.hora_inicio', '<', $reservacion->hora_fin)
                    ->where('rod.hora_fin', '>', $reservacion->hora_inicio);
            })
            ->where('rod.id_reserva', '!=', $id) // Excluir esta misma reservación
            ->exists();

        // Si es titular y hace sus propias reservas, verificamos con tabla "reservaciones_on_demand"
        $tieneConflictoTitular = DB::table('reservaciones_on_demand')
            ->where('id_socio_titular', $acompananteId) // dueño de reserva
            ->where('fecha_reserva', $reservacion->fecha_reserva)
            ->where('estatus_operativo', '!=', 'CANCELADA')
            ->where(function ($query) use ($reservacion) {
                $query->where('hora_inicio', '<', $reservacion->hora_fin)
                    ->where('hora_fin', '>', $reservacion->hora_inicio);
            })
            ->where('id_reserva', '!=', $id)
            ->exists();

        if ($tieneConflicto || $tieneConflictoTitular) {
            return response()->json([
                'success' => false,
                'message' => 'El acompañante tiene conflicto de horario con otra reservación.'
            ], 409);
        }

        // 3. Validación: No superar la capacidad máxima del espacio
        $espacio = DB::table('espacios_fisicos')->where('id_espacio', $reservacion->id_espacio)->first();
        if ($espacio && isset($espacio->capacidad_maxima)) {
            // Contar asistentes (1 titular + cantidad en tabla pivote para esta reservacion)
            $asistentesAdicionales = DB::table('reservaciones_usuarios')
                ->where('reservacion_id', $id)
                ->count();

            $totalAsistentes = 1 + $asistentesAdicionales;

            if ($totalAsistentes >= $espacio->capacidad_maxima) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se ha alcanzado la capacidad máxima del espacio'
                ], 400);
            }
        }

        // Validar que no se haya agregado ya a este usuario a esta misma reserva
        $yaAgregado = DB::table('reservaciones_usuarios')
            ->where('reservacion_id', $id)
            ->where('usuario_id', $acompananteId)
            ->exists();

        if ($yaAgregado) {
            return response()->json([
                'success' => false,
                'message' => 'El acompañante ya ha sido agregado a esta reservación o es el titular.'
            ], 400);
        }

        // Insertar en tabla pivote de acompañantes
        DB::table('reservaciones_usuarios')->insert([
            'reservacion_id' => $id,
            'usuario_id' => $acompananteId,
            'agregado_por' => $request->user()->id ?? null,
            // 'created_at' => now(), // Descomentar si la tabla usa timestamps en DB
            // 'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Acompañante agregado exitosamente.'
        ], 201);
    }

    public function syncAcompanantesDraft(Request $request, $id)
    {
        $request->validate([
            'acompanantes' => 'array'
        ]);

        $reserva = Reservacion::where('id_reserva', $id)->first();

        if (!$reserva) {
            return response()->json(['success' => false, 'message' => 'Reservación no encontrada'], 404);
        }

        if ($reserva->id_socio_titular !== $request->user()->user_id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para modificar este borrador'], 403);
        }

        if ($reserva->estatus_operativo !== 'PENDIENTE') {
            return response()->json(['success' => false, 'message' => 'La reservación ya no es un borrador (estatus: ' . $reserva->estatus_operativo . ')'], 400);
        }

        // Guardamos los acompañantes (Laravel lo serializa automáticamente gracias a $casts)
        $reserva->acompanantes_draft = $request->acompanantes ?? [];
        $reserva->save();

        return response()->json([
            'success' => true, 
            'message' => 'Acompañantes del borrador sincronizados'
        ], 200);
    }

    // 2. CONFIRMAR RESERVA (Step 5 del Front)
    public function confirm(Request $request)
    {
        $id = $request->id_reserva;

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Falta el ID de la reservación'], 400);
        }

        $reserva = Reservacion::where('id_reserva', $id)->first();

        if (!$reserva) {
            return response()->json(['success' => false, 'message' => 'No se encontró la reservación'], 404);
        }

        // Protección contra doble envío
        if ($reserva->estatus_operativo === 'ACTIVA' || $reserva->estatus_operativo === 'CONFIRMADA') {
            return response()->json(['success' => false, 'message' => 'Esta reservación ya fue confirmada previamente.'], 409);
        }

        if ($reserva->estatus_operativo !== 'PENDIENTE') {
            return response()->json(['success' => false, 'message' => 'La reservación no está en estado PENDIENTE.'], 400);
        }

        // Validar que no haya expirado
        if ($reserva->fecha_expiracion && Carbon::parse($reserva->fecha_expiracion)->isPast()) {
            return response()->json(['success' => false, 'message' => 'La reservación ha expirado. Por favor, crea una nueva.'], 400);
        }

        // Verificar propiedad
        if ($reserva->id_socio_titular !== $request->user()->user_id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para confirmar esta reservación.'], 403);
        }

        try {
            return DB::transaction(function () use ($reserva) {
                // Leer acompañantes del draft almacenado en BD
                $draft = $reserva->acompanantes_draft;
                
                if (is_string($draft)) {
                    $draft = json_decode($draft, true) ?? [];
                }
                
                if (!is_array($draft)) {
                    $draft = [];
                }

                // Validar capacidad del espacio
                $espacio = EspacioFisico::where('id_espacio', $reserva->id_espacio)->first();
                
                if ($espacio && $espacio->capacidad_maxima) {
                    $totalAsistentes = count($draft) + 1; // +1 por el titular
                    
                    if ($totalAsistentes > $espacio->capacidad_maxima) {
                        return response()->json([
                            'success' => false,
                            'message' => "La cancha tiene capacidad para {$espacio->capacidad_maxima} personas. Tienes " . count($draft) . " acompañantes + tú = {$totalAsistentes}."
                        ], 422);
                    }
                }

                // Cambiar estatus a ACTIVA y limpiar datos temporales
                $reserva->estatus_operativo = 'ACTIVA';
                $reserva->fecha_expiracion = null;
                // Mantenemos el draft para referencia histórica pero limpiamos la expiración
                $reserva->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Reservación confirmada exitosamente'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la confirmación: ' . $e->getMessage()
            ], 500);
        }
    }

    // 3. CANCELAR RESERVA (Si el usuario se sale a la mitad)
    public function cancel(Request $request)
    {
        $id = $request->id_reserva;

        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Falta el ID de la reservación'], 400);
        }

        Reservacion::where('id_reserva', $id)
            ->update(['estatus_operativo' => 'CANCELADA']);

        return response()->json(['success' => true, 'message' => 'Reservación cancelada correctamente']);
    }

    public function getActiveDraft(Request $request)
    {

        $id_socio = $request->user()->user_id;

        $reserva = Reservacion::where('id_socio_titular', $id_socio)
            ->where('estatus_operativo', 'PENDIENTE')
            ->where('fecha_expiracion', '>', now())
            ->with([
                'espacioFisico:id_espacio,nombre_espacio', // Trae solo ID y Nombre
                'disciplina:id_disciplina,nombre_disciplina' // Trae solo ID y Nombre
            ])
            ->first();

        //Hacerlo más eficiente para que sólo me devuelva los nombres de
        //espacio y disciplina de la reserva pls
        // para no devolver todo el objeto (solo id y nombre)

        return response()->json([
            'success' => !!$reserva,
            'reserva' => $reserva
        ]);
    }
}
