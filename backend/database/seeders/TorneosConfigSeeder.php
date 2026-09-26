<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Disciplina;
use App\Models\CategoriaTorneo;
use Illuminate\Support\Facades\DB;

class TorneosConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Iniciando configuración y normalización para el módulo de torneos...');

        DB::beginTransaction();

        try {
            $this->normalizarYConfigurarDisciplinas();
            $this->limpiarYConfigurarCategorias();

            DB::commit();
            $this->command?->info('TorneosConfigSeeder ejecutado con éxito y cambios confirmados (commit).');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command?->error('Error durante la ejecución del seeder. Se realizó rollback de todos los cambios: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Normaliza los nombres de disciplinas (removiendo sufijos de edad) y
     * establece aplica_para_torneos = true exclusivamente para las 7 autorizadas.
     */
    private function normalizarYConfigurarDisciplinas(): void
    {
        $disciplinasPermitidas = [
            'Pádel',
            'Fútbol',
            'Voleibol',
            'Frontenis',
            'Squash',
            'Básquetbol',
            'Tenis',
        ];

        // Mapeo para normalizar variaciones o disciplinas con sufijos de edad
        $mapeoNormalizacion = [
            // Fútbol
            'futbol' => 'Fútbol',
            'futbol infantil' => 'Fútbol',
            'futbol juvenil' => 'Fútbol',
            'futbol adulto' => 'Fútbol',
            'futbol adultos' => 'Fútbol',
            'fútbol infantil' => 'Fútbol',
            'fútbol juvenil' => 'Fútbol',
            'fútbol adulto' => 'Fútbol',
            'fútbol adultos' => 'Fútbol',

            // Pádel
            'padel' => 'Pádel',
            'padel infantil' => 'Pádel',
            'padel juvenil' => 'Pádel',
            'padel adulto' => 'Pádel',
            'padel adultos' => 'Pádel',
            'pádel infantil' => 'Pádel',
            'pádel juvenil' => 'Pádel',
            'pádel adulto' => 'Pádel',
            'pádel adultos' => 'Pádel',

            // Voleibol
            'volibol' => 'Voleibol',
            'voleibol infantil' => 'Voleibol',
            'voleibol juvenil' => 'Voleibol',
            'voleibol adulto' => 'Voleibol',
            'voleibol adultos' => 'Voleibol',
            'voley' => 'Voleibol',
            'vóley' => 'Voleibol',
            'vóleibol' => 'Voleibol',

            // Frontenis
            'frontenis infantil' => 'Frontenis',
            'frontenis juvenil' => 'Frontenis',
            'frontenis adulto' => 'Frontenis',
            'frontenis adultos' => 'Frontenis',

            // Squash
            'squash infantil' => 'Squash',
            'squash juvenil' => 'Squash',
            'squash adulto' => 'Squash',
            'squash adultos' => 'Squash',

            // Básquetbol
            'basquetbol' => 'Básquetbol',
            'baloncesto' => 'Básquetbol',
            'basquet' => 'Básquetbol',
            'básquet' => 'Básquetbol',
            'basquetbol infantil' => 'Básquetbol',
            'basquetbol juvenil' => 'Básquetbol',
            'basquetbol adulto' => 'Básquetbol',
            'basquetbol adultos' => 'Básquetbol',
            'básquetbol infantil' => 'Básquetbol',
            'básquetbol juvenil' => 'Básquetbol',
            'básquetbol adulto' => 'Básquetbol',
            'básquetbol adultos' => 'Básquetbol',

            // Tenis
            'tenis infantil' => 'Tenis',
            'tenis juvenil' => 'Tenis',
            'tenis adulto' => 'Tenis',
            'tenis adultos' => 'Tenis',
        ];

        // 1. Normalizar registros existentes según el mapeo
        $todasDisciplinas = Disciplina::all();
        foreach ($todasDisciplinas as $disciplina) {
            $nombreLower = trim(mb_strtolower($disciplina->nombre_disciplina, 'UTF-8'));
            if (isset($mapeoNormalizacion[$nombreLower])) {
                $nombreDestino = $mapeoNormalizacion[$nombreLower];
                if ($disciplina->nombre_disciplina !== $nombreDestino) {
                    $existeDestino = Disciplina::where('nombre_disciplina', $nombreDestino)
                        ->where('id_disciplina', '!=', $disciplina->id_disciplina)
                        ->exists();

                    if (!$existeDestino) {
                        $nombreOriginal = $disciplina->nombre_disciplina;
                        $disciplina->nombre_disciplina = $nombreDestino;
                        $disciplina->save();
                        $this->command?->info("Disciplina '{$nombreOriginal}' normalizada a '{$nombreDestino}'.");
                    }
                }
            }
        }

        // 2. Garantizar la existencia de las 7 disciplinas permitidas
        foreach ($disciplinasPermitidas as $nombre) {
            $disciplina = Disciplina::where('nombre_disciplina', $nombre)->first();
            if (!$disciplina) {
                $this->command?->warn("Disciplina '{$nombre}' no encontrada. Creándola automáticamente...");
                Disciplina::create([
                    'nombre_disciplina' => $nombre,
                    'estatus' => 'ACTIVO',
                    'aplica_para_torneos' => true,
                ]);
            }
        }

        // 3. Ejecutar UPDATE masivo: desactivar aplica_para_torneos en todas las no permitidas
        Disciplina::whereNotIn('nombre_disciplina', $disciplinasPermitidas)
            ->update(['aplica_para_torneos' => false]);

        // 4. Activar aplica_para_torneos estrictamente en las 7 permitidas
        Disciplina::whereIn('nombre_disciplina', $disciplinasPermitidas)
            ->update(['aplica_para_torneos' => true]);

        $this->command?->info("Disciplinas actualizadas. 7 disciplinas marcadas con aplica_para_torneos = true.");
    }

    /**
     * Limpia la tabla categorias_torneo dejando únicamente Infantil, Juvenil y Adulto
     * con genero_requerido en null/neutral de manera idempotente, reasignando previamente
     * las llaves foráneas en tablas dependientes para evitar violaciones de integridad referencial.
     */
    private function limpiarYConfigurarCategorias(): void
    {
        // 1. Asegurar la existencia de las 3 categorías autorizadas con sus rangos de edad y género neutral
        $catInfantil = CategoriaTorneo::updateOrCreate(
            ['nombre_categoria' => 'Infantil'],
            [
                'edad_minima' => 6,
                'edad_maxima' => 14,
                'genero_requerido' => 'MIXTO',
            ]
        );

        $catJuvenil = CategoriaTorneo::updateOrCreate(
            ['nombre_categoria' => 'Juvenil'],
            [
                'edad_minima' => 15,
                'edad_maxima' => 17,
                'genero_requerido' => 'MIXTO',
            ]
        );

        $catAdulto = CategoriaTorneo::updateOrCreate(
            ['nombre_categoria' => 'Adulto'],
            [
                'edad_minima' => 18,
                'edad_maxima' => 99,
                'genero_requerido' => 'MIXTO',
            ]
        );

        $idsAutorizados = [
            $catInfantil->id_categoria,
            $catJuvenil->id_categoria,
            $catAdulto->id_categoria,
        ];

        // 2. Buscar categorías obsoletas que no coincidan con las autorizadas
        $categoriasObsoletas = CategoriaTorneo::whereNotIn('id_categoria', $idsAutorizados)->get();

        foreach ($categoriasObsoletas as $catObsoleta) {
            $nombreLower = mb_strtolower($catObsoleta->nombre_categoria, 'UTF-8');

            if (str_contains($nombreLower, 'infantil') || str_contains($nombreLower, 'niñ')) {
                $destino = $catInfantil;
            } elseif (str_contains($nombreLower, 'juvenil') || str_contains($nombreLower, 'sub-')) {
                $destino = $catJuvenil;
            } else {
                $destino = $catAdulto;
            }

            // Reasignar referencias en tablas que usan id_categoria evitando colisiones de id_interno
            if (DB::getSchemaBuilder()->hasTable('torneos') && DB::getSchemaBuilder()->hasColumn('torneos', 'id_categoria')) {
                DB::table('torneos')
                    ->where('id_categoria', $catObsoleta->id_categoria)
                    ->update(['id_categoria' => $destino->id_categoria]);
            }

            if (DB::getSchemaBuilder()->hasTable('equipos_torneo') && DB::getSchemaBuilder()->hasColumn('equipos_torneo', 'id_categoria')) {
                $equipos = DB::table('equipos_torneo')
                    ->where('id_categoria', $catObsoleta->id_categoria)
                    ->orderBy('id_equipo_torneo')
                    ->get();

                $maxIdInternoEquipo = (int) DB::table('equipos_torneo')
                    ->where('id_categoria', $destino->id_categoria)
                    ->max('id_interno');

                foreach ($equipos as $equipo) {
                    $maxIdInternoEquipo++;
                    DB::table('equipos_torneo')
                        ->where('id_equipo_torneo', $equipo->id_equipo_torneo)
                        ->update([
                            'id_categoria' => $destino->id_categoria,
                            'id_interno' => $maxIdInternoEquipo,
                        ]);
                }
            }

            if (DB::getSchemaBuilder()->hasTable('participantes_torneo') && DB::getSchemaBuilder()->hasColumn('participantes_torneo', 'id_categoria')) {
                $participantes = DB::table('participantes_torneo')
                    ->where('id_categoria', $catObsoleta->id_categoria)
                    ->orderBy('id_participante_torneo')
                    ->get();

                $maxIdInternoPart = (int) DB::table('participantes_torneo')
                    ->where('id_categoria', $destino->id_categoria)
                    ->max('id_interno');

                foreach ($participantes as $part) {
                    $maxIdInternoPart++;
                    DB::table('participantes_torneo')
                        ->where('id_participante_torneo', $part->id_participante_torneo)
                        ->update([
                            'id_categoria' => $destino->id_categoria,
                            'id_interno' => $maxIdInternoPart,
                        ]);
                }
            }

            $this->command?->info("Reasignadas referencias de categoría '{$catObsoleta->nombre_categoria}' (ID: {$catObsoleta->id_categoria}) a '{$destino->nombre_categoria}' (ID: {$destino->id_categoria}).");
        }

        // 3. Eliminar físicamente las categorías obsoletas que ya no tienen referencias
        CategoriaTorneo::whereNotIn('id_categoria', $idsAutorizados)->delete();

        $this->command?->info("Categorías de torneo depuradas exitosamente. Registros conservados: Infantil, Juvenil, Adulto.");
    }
}

