<?php

namespace App\Actions\Torneo;

use App\Models\ParticipantesTorneo;
use App\Models\EquiposTorneo;
use App\Jobs\GenerarQRParticipanteJob;
use App\Models\Torneo;
use App\Models\SocioTitular;
use App\Models\MiembrosFamiliares;

class AprobarPreRegistroAction
{
    public static function execute($preRegistro)
    {

        /*
        |--------------------------------------------------------------------------
        | INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        if ($preRegistro->tipo == 'INDIVIDUAL') {

            $datos = $preRegistro->datos_participante;

            // Obtener Torneo y Categoría
            $torneo = Torneo::find($preRegistro->id_torneo);
            $idCategoria = $torneo->id_categoria;

            // Determinar tipo de participante
            $tipoEntidad = 'COMPETIDOR_EXTERNO';
            $referenciaId = 0;
            $participanteType = null;
            $participanteId = null;

            // Buscar socio titular
            $socio = SocioTitular::where('correo_electronico', $datos['correo'])->first();
            if ($socio) {
                $tipoEntidad = 'SOCIO_TITULAR';
                $referenciaId = $socio->id_socio;
                $participanteType = SocioTitular::class;
                $participanteId = $socio->id_socio;
            } else {
                // Buscar miembro familiar
                $familiar = MiembrosFamiliares::where('correo', $datos['correo'])->first();
                if ($familiar) {
                    $tipoEntidad = 'MIEMBRO_FAMILIAR';
                    $referenciaId = $familiar->id_miembro;
                    $participanteType = MiembrosFamiliares::class;
                    $participanteId = $familiar->id_miembro;
                }
            }

            $participante = ParticipantesTorneo::create([
                // FK torneo
                'id_torneo' => $preRegistro->id_torneo,

                // Categoría (FALTABA ESTO)
                'id_categoria' => $idCategoria,

                // Tipo entidad
                'tipo_entidad' => $tipoEntidad,

                // Referencia
                'referencia_id' => $referenciaId,

                // Ranking
                'ranking_declarado' => $datos['ranking_declarado'] ?? 0,

                // Siembra (Agregado para consistencia)
                'siembra_ranking' => null,

                // Fecha inscripción
                'fecha_inscripcion' => now(),

                // Estado torneo
                'estatus_participacion' => 'ACTIVO',

                // Estado inscripción
                'estatus_inscripcion' => 'CONFIRMADO',

                // Morph
                'participante_type' => $participanteType,
                'participante_id' => $participanteId,

                // Temporal
                'id_interno' => 0,

                // Equipo
                'id_equipo' => null,
            ]);

            // Generar QR de forma síncrona
            $qrData = 'Participante ID: ' . $participante->id_participante_torneo;
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrData);

            $participante->qr_codigo = $qrUrl;
            $participante->qr_estatus = 'GENERADO';
            $participante->save();

            // Aprobar preregistro
            $preRegistro->estatus = 'APROBADO';
            $preRegistro->save();

            // Dispatch QR (sólo para envío de correo)
            GenerarQRParticipanteJob::dispatch($participante, $datos['correo']);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | EQUIPO
        |--------------------------------------------------------------------------
        */
        if ($preRegistro->tipo == 'EQUIPO') {

            $datos = $preRegistro->datos_participante;

            /*
            |--------------------------------------------------------------------------
            | OBTENER TORNEO Y CATEGORÍA
            |--------------------------------------------------------------------------
            */

            $torneo = Torneo::find($preRegistro->id_torneo);

            $idCategoria = $torneo->id_categoria;

            /*
            |--------------------------------------------------------------------------
            | CREAR PARTICIPANTES
            |--------------------------------------------------------------------------
            */

            $participantes = [];
            $correosMap = [];

            foreach ($datos['integrantes'] as $integrante) {

                /*
                |--------------------------------------------------------------------------
                | DETERMINAR TIPO DE PARTICIPANTE
                |--------------------------------------------------------------------------
                */

                $tipoEntidad = 'COMPETIDOR_EXTERNO';

                $referenciaId = 0;

                $participanteType = null;

                $participanteId = null;

                // Buscar socio titular
                $socio = SocioTitular::where(
                    'correo_electronico',
                    $integrante['correo']
                )->first();

                if ($socio) {

                    $tipoEntidad = 'SOCIO_TITULAR';

                    $referenciaId = $socio->id_socio;

                    $participanteType = SocioTitular::class;

                    $participanteId = $socio->id_socio;
                }

                // Buscar miembro familiar
                else {

                    $familiar = MiembrosFamiliares::where(
                        'correo',
                        $integrante['correo']
                    )->first();

                    if ($familiar) {

                        $tipoEntidad = 'MIEMBRO_FAMILIAR';

                        $referenciaId = $familiar->id_miembro;

                        $participanteType = MiembrosFamiliares::class;

                        $participanteId = $familiar->id_miembro;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | CREAR PARTICIPANTE
                |--------------------------------------------------------------------------
                */

                $participante = ParticipantesTorneo::create([

                    // FK torneo
                    'id_torneo' => $preRegistro->id_torneo,

                    // Categoría
                    'id_categoria' => $idCategoria,

                    // Tipo entidad
                    'tipo_entidad' => $tipoEntidad,

                    // Referencia
                    'referencia_id' => $referenciaId,

                    // Ranking declarado
                    'ranking_declarado' =>
                        $integrante['ranking_declarado'],

                    // Ranking siembra
                    'siembra_ranking' => null,

                    // Fecha inscripción
                    'fecha_inscripcion' => now(),

                    // Estado participación
                    'estatus_participacion' => 'ACTIVO',

                    // Estado inscripción
                    'estatus_inscripcion' => 'CONFIRMADO',

                    // Morph
                    'participante_type' => $participanteType,

                    'participante_id' => $participanteId,

                    // Temporal
                    'id_interno' => 0,

                    // Aún NO existe equipo
                    'id_equipo' => null,

                    // QR
                    'qr_codigo' => null,

                    'qr_estatus' => 'PENDIENTE'
                ]);

                $participantes[] = $participante;
                $correosMap[] = $integrante['correo'];
            }

            /*
            |--------------------------------------------------------------------------
            | DETERMINAR TIPO DEL EQUIPO
            |--------------------------------------------------------------------------
            */

            $tipoEquipo = 'COMPETIDOR_EXTERNO';

            $referenciaEquipo = 0;

            // Capitán
            $capitan = $participantes[0];

            if ($capitan->tipo_entidad == 'SOCIO_TITULAR') {

                $tipoEquipo = 'SOCIO_TITULAR';

                $referenciaEquipo = $capitan->referencia_id;
            } elseif ($capitan->tipo_entidad == 'MIEMBRO_FAMILIAR') {

                $tipoEquipo = 'MIEMBRO_FAMILIAR';

                $referenciaEquipo = $capitan->referencia_id;
            }

            /*
            |--------------------------------------------------------------------------
            | CREAR EQUIPO
            |--------------------------------------------------------------------------
            */
            $maxIdInterno = EquiposTorneo::where('id_categoria', $idCategoria)->max('id_interno');
            $nextIdInterno = ($maxIdInterno !== null) ? $maxIdInterno + 1 : 0;

            $equipo = EquiposTorneo::create([

                // Capitán
                'id_participante_torneo' =>
                    $capitan->id_participante_torneo,

                // Categoría
                'id_categoria' => $idCategoria,

                // Tipo
                'tipo_entidad' => $tipoEquipo,

                // Referencia
                'referencia_id' => $referenciaEquipo,

                // Temporal
                'id_interno' => $nextIdInterno,

                // Ranking
                'siembra_ranking' => null,

                // Posición
                'posicion_actual' => 0,

                // Puntos
                'puntos_torneo' => 0,

                // Estado
                'estatus_participacion' => 'ACTIVO',

                // Torneo
                'id_torneo' => $preRegistro->id_torneo,

                // Nombre
                'nombre_equipo' =>
                    $datos['nombre_equipo'],

                // Estado equipo
                'estatus_equipo' => 'ACTIVO'
            ]);

            /*
            |--------------------------------------------------------------------------
            | ASIGNAR EQUIPO A PARTICIPANTES
            |--------------------------------------------------------------------------
            */

            foreach ($participantes as $index => $participante) {

                $participante->id_equipo =
                    $equipo->id_equipo_torneo;

                // Generar QR de forma síncrona
                $qrData = 'Participante ID: ' . $participante->id_participante_torneo;
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrData);

                $participante->qr_codigo = $qrUrl;
                $participante->qr_estatus = 'GENERADO';
                $participante->save();

                // Dispatch QR (sólo para envío de correo)
                GenerarQRParticipanteJob::dispatch($participante, $correosMap[$index]);
            }

            /*
            |--------------------------------------------------------------------------
            | APROBAR PREREGISTRO
            |--------------------------------------------------------------------------
            */

            $preRegistro->estatus = 'APROBADO';

            $preRegistro->save();
        }
    }
}
