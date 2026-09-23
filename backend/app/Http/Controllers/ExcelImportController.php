<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\SocioTitular;
use App\Models\MiembrosFamiliares;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExcelImportController extends Controller
{
    // ── Valores permitidos según el Diccionario de Datos ────────
    private const ENUM_TIPO_SOCIO      = ['ACCIONISTA', 'RENTISTA'];
    private const ENUM_MODALIDAD_PLAN  = ['INDIVIDUAL', 'FAMILIAR'];
    private const ENUM_GENERO          = ['M', 'F'];
    private const ENUM_ESTATUS_CUENTA  = ['AL_CORRIENTE', 'MOROSO', 'SUSPENDIDO'];
    private const ENUM_PARENTESCO      = ['CONYUGE', 'HIJO/A', 'OTRO'];

    // ── Longitudes máximas (character varying) ─────────────────
    private const MAX_NOMBRE      = 100;
    private const MAX_CORREO      = 150;
    private const MAX_NUM_ACCION  = 30;
    private const MAX_CORREO_FAM  = 150;

    // ── Columnas esperadas en la hoja única ────────────────────
    private const UNIFIED_COLUMNS = [
        'numero_accion',
        'tipo_accion',
        'estatus_accion',
        'rol',
        'nombre_completo',
        'genero',
        'fecha_nacimiento',
        'parentesco',
        'email',
    ];

    /**
     * POST /api/v1/socios/import-excel
     *
     * Importar un archivo Excel/CSV y realizar UPDATEs sobre
     * socios_titulares y miembros_familiares.
     */
    public function importSocios(Request $request)
    {
        // ── 1. Autorización ────────────────────────────────────
        $user = $request->user();
        if (!in_array($user->rol, ['gerente', 'subgerente'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo gerentes y subgerentes pueden importar datos.',
            ], 403);
        }

        // ── 2. Validar archivo ─────────────────────────────────
        $validator = Validator::make($request->all(), [
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240', // max 10 MB
        ], [
            'archivo.required' => 'Debe adjuntar un archivo Excel o CSV.',
            'archivo.file'     => 'El archivo adjunto no es válido.',
            'archivo.mimes'    => 'Solo se aceptan archivos .xlsx, .xls o .csv.',
            'archivo.max'      => 'El archivo no debe exceder 10 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $file = $request->file('archivo');
            $spreadsheet = IOFactory::load($file->getPathname());

            // Procesar la primera hoja (independiente del nombre)
            $sheet = $spreadsheet->getSheet(0);
            $importResult = $this->procesarHojaUnica($sheet);

            return response()->json([
                'success'         => $importResult['success'],
                'resumen'         => $importResult['resumen'],
                'errores'         => $importResult['errores'],
                'actualizaciones' => $importResult['actualizaciones'],
            ], 200);

        } catch (\Exception $e) {
            Log::error('ExcelImportController@importSocios — Error inesperado', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Normaliza los valores a enums compatibles con la Base de Datos.
     */
    private function normalizarValor(string $campo, $valor): ?string
    {
        if ($valor === null || trim((string)$valor) === '') {
            return null;
        }
        $val = mb_strtolower(trim((string)$valor));

        switch ($campo) {
            case 'tipo_socio':
                if (str_contains($val, 'propia')) return 'ACCIONISTA';
                if (str_contains($val, 'renta')) return 'RENTISTA';
                return mb_strtoupper(trim((string)$valor));

            case 'modalidad_plan':
                if (str_contains($val, 'individual')) return 'INDIVIDUAL';
                if (str_contains($val, 'familiar')) return 'FAMILIAR';
                return mb_strtoupper(trim((string)$valor));

            case 'genero':
                if ($val === 'masculino' || $val === 'm') return 'M';
                if ($val === 'femenino' || $val === 'f') return 'F';
                return mb_strtoupper(trim((string)$valor));

            case 'parentesco':
                if (str_contains($val, 'esposo') || str_contains($val, 'esposa') || str_contains($val, 'conyuge')) return 'CONYUGE';
                if (str_contains($val, 'hijo') || str_contains($val, 'hija')) return 'HIJO/A';
                return 'OTRO';
        }

        return $valor;
    }

    /**
     * Normaliza y limpia una dirección de correo electrónico,
     * convirtiéndola a minúsculas y removiendo acentos y espacios.
     */
    private function normalizarEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }
        $email = mb_strtolower(trim($email));
        $accents = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ];
        $email = strtr($email, $accents);
        return preg_replace('/\s+/', '', $email);
    }

    /**
     * Sincroniza e importa la hoja de socios/familiares unificada de forma masiva y atómica.
     */
    private function procesarHojaUnica($sheet): array
    {
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return [
                'success' => false,
                'resumen' => [
                    'socios' => ['total' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => 0],
                    'familiares' => ['total' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => 0]
                ],
                'errores'         => [['hoja' => 'Base_Socios', 'fila' => 0, 'campo' => '-', 'valor' => '-', 'mensaje' => 'La hoja está vacía o solo tiene encabezados.']],
                'actualizaciones' => [],
            ];
        }

        // Mapear encabezados
        $headerRow = array_shift($rows);
        $columnMap = $this->mapearEncabezados($headerRow, self::UNIFIED_COLUMNS);

        if (empty($columnMap) || !isset($columnMap['numero_accion']) || !isset($columnMap['rol'])) {
            return [
                'success' => false,
                'resumen' => [
                    'socios' => ['total' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => 0],
                    'familiares' => ['total' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => 0]
                ],
                'errores'         => [['hoja' => 'Base_Socios', 'fila' => 1, 'campo' => '-', 'valor' => '-', 'mensaje' => 'No se encontraron las columnas requeridas (Numero_Accion o Rol) en los encabezados.']],
                'actualizaciones' => [],
            ];
        }

        // ── 1. Extraer datos ──
        $numerosAccion = [];
        $rowsData = [];
        foreach ($rows as $rowIndex => $row) {
            $filaNum = $rowIndex + 1;
            $rawExcelData = $this->extraerValoresMapeados($row, $columnMap);
            $numAccion = trim($rawExcelData['numero_accion'] ?? '');
            if ($numAccion !== '') {
                $numerosAccion[] = $numAccion;
            }
            $rowsData[$filaNum] = $rawExcelData;
        }
        $numerosAccion = array_unique($numerosAccion);
        $sociosDb = SocioTitular::whereIn('numero_accion', $numerosAccion)->get()->keyBy('numero_accion');
        $socioIds = $sociosDb->pluck('id_socio')->toArray();
        $familiaresDb = MiembrosFamiliares::whereIn('socio_id', $socioIds)->get();
        $familiaresLookup = [];
        foreach ($familiaresDb as $fam) {
            $key = $fam->socio_id . '_' . mb_strtolower(trim($fam->nombre_completo));
            $familiaresLookup[$key] = $fam;
        }

        // Precargar todos los correos registrados en la tabla 'users'
        $usersDbEmails = User::pluck('user_id', 'email')
            ->mapWithKeys(fn($userId, $email) => [mb_strtolower(trim($email)) => $userId])
            ->toArray();
            
        $seenEmailsInImport = [];

        $resumen = [
            'socios'     => ['total' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => 0],
            'familiares' => ['total' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => 0],
        ];
        $errores         = [];
        $actualizaciones = [];

        $sociosToInsert = [];
        $sociosToUpdate = [];
        $usersToUpdateEmail = [];
        $familiaresToInsert = [];
        $familiaresToUpdate = [];
        $familiaresQueueNewPartners = [];

        // ── 4. Bucle principal ──
        foreach ($rowsData as $filaNum => $rawExcelData) {
            $rol = mb_strtolower(trim($rawExcelData['rol'] ?? ''));
            if (empty($rol)) continue;

            $numAccion = trim($rawExcelData['numero_accion'] ?? '');
            if (empty($numAccion)) {
                $errores[] = ['hoja' => 'Base_Socios', 'fila' => $filaNum, 'campo' => 'numero_accion', 'valor' => '', 'mensaje' => 'El número de acción está vacío.'];
                $resumen['socios']['errores']++;
                continue;
            }

            $nombreCompleto = trim($rawExcelData['nombre_completo'] ?? '');
            if (empty($nombreCompleto)) {
                $errores[] = ['hoja' => 'Base_Socios', 'fila' => $filaNum, 'campo' => 'nombre_completo', 'valor' => '', 'mensaje' => 'El nombre completo está vacío.'];
                $resumen['socios']['errores']++;
                continue;
            }

            if ($rol === 'titular') {
                $resumen['socios']['total']++;
                $mappedData = [
                    'numero_accion'      => $numAccion,
                    'nombre_completo'    => $nombreCompleto,
                    'tipo_socio'         => $this->normalizarValor('tipo_socio', $rawExcelData['estatus_accion'] ?? null),
                    'modalidad_plan'     => $this->normalizarValor('modalidad_plan', $rawExcelData['tipo_accion'] ?? null),
                    'genero'             => $this->normalizarValor('genero', $rawExcelData['genero'] ?? null),
                    'correo_electronico' => $this->normalizarEmail($rawExcelData['email'] ?? null),
                    'fecha_nacimiento'   => trim($rawExcelData['fecha_nacimiento'] ?? ''),
                ];

                $erroresFila = $this->validarFilaSocio($mappedData, $filaNum);

                // ── Validar unicidad de correo electrónico en users ──
                $emailNorm = !empty($mappedData['correo_electronico']) ? mb_strtolower(trim($mappedData['correo_electronico'])) : null;
                if ($emailNorm !== null) {
                    $socio = $sociosDb->get($numAccion);
                    if (!$socio) {
                        // Socio es nuevo. El email no debe existir en la BD.
                        if (isset($usersDbEmails[$emailNorm])) {
                            $erroresFila[] = [
                                'hoja' => 'Base_Socios',
                                'fila' => $filaNum,
                                'campo' => 'email',
                                'valor' => $mappedData['correo_electronico'],
                                'mensaje' => "El correo electrónico ya está registrado por otro socio (ID Socio: {$usersDbEmails[$emailNorm]}) en el sistema."
                            ];
                        }
                        // Tampoco debe estar duplicado en el mismo lote de nuevos socios
                        if (isset($seenEmailsInImport[$emailNorm])) {
                            $erroresFila[] = [
                                'hoja' => 'Base_Socios',
                                'fila' => $filaNum,
                                'campo' => 'email',
                                'valor' => $mappedData['correo_electronico'],
                                'mensaje' => "El correo electrónico está duplicado en el archivo Excel (también usado en la fila {$seenEmailsInImport[$emailNorm]})."
                            ];
                        } else {
                            $seenEmailsInImport[$emailNorm] = $filaNum;
                        }
                    } else {
                        // Socio es existente (UPDATE). El email no debe pertenecer a otro usuario.
                        if (isset($usersDbEmails[$emailNorm]) && $usersDbEmails[$emailNorm] != $socio->id_socio) {
                            $erroresFila[] = [
                                'hoja' => 'Base_Socios',
                                'fila' => $filaNum,
                                'campo' => 'email',
                                'valor' => $mappedData['correo_electronico'],
                                'mensaje' => "El correo electrónico ya está registrado por otro socio (ID Socio: {$usersDbEmails[$emailNorm]})."
                            ];
                        }
                    }
                }

                if (!empty($erroresFila)) {
                    $errores = array_merge($errores, $erroresFila);
                    $resumen['socios']['errores']++;
                    continue;
                }

                $socio = $sociosDb->get($numAccion);
                if (!$socio) {
                    $mappedData['tipo_socio'] = $mappedData['tipo_socio'] ?? 'RENTISTA';
                    $mappedData['modalidad_plan'] = $mappedData['modalidad_plan'] ?? 'INDIVIDUAL';
                    $mappedData['genero'] = $mappedData['genero'] ?? 'M';
                    $mappedData['estatus_cuenta'] = 'AL_CORRIENTE';
                    $fechaNacExcel = trim($mappedData['fecha_nacimiento']);
                    $mappedData['fecha_nacimiento'] = !empty($fechaNacExcel) ? ($this->normalizarFecha($fechaNacExcel) ?? '1990-01-01') : '1990-01-01';
                    $mappedData['fecha_afiliacion'] = date('Y-m-d');
                    $mappedData['correo_electronico'] = !empty($mappedData['correo_electronico']) ? $mappedData['correo_electronico'] : 'socio_' . $numAccion . '@socdephub.com';

                    $sociosToInsert[$numAccion] = [
                        'numero_accion'      => $numAccion,
                        'nombre_completo'    => $mappedData['nombre_completo'],
                        'tipo_socio'         => $mappedData['tipo_socio'],
                        'modalidad_plan'     => $mappedData['modalidad_plan'],
                        'genero'             => $mappedData['genero'],
                        'correo_electronico' => $mappedData['correo_electronico'],
                        'fecha_nacimiento'   => $mappedData['fecha_nacimiento'],
                        'fecha_afiliacion'   => $mappedData['fecha_afiliacion'],
                        'estatus_cuenta'     => $mappedData['estatus_cuenta'],
                        'contador_no_shows'  => 0,
                        'retrasos_ludoteca'  => 0,
                        'estatus_penalizacion' => 'SIN_PENALIZACION'
                    ];
                } else {
                    $updateData = $this->construirUpdateDataSocio($socio, $mappedData);
                    if (empty($updateData)) {
                        $resumen['socios']['sin_cambios']++;
                        continue;
                    }
                    $sociosToUpdate[] = [$socio, $updateData];
                    if (isset($updateData['correo_electronico'])) {
                        $usersToUpdateEmail[] = [$socio->id_socio, $updateData['correo_electronico']];
                    }
                }
            } else if ($rol === 'miembro') {
                $resumen['familiares']['total']++;
                $mappedData = [
                    'numero_accion'    => $numAccion,
                    'nombre_completo'  => $nombreCompleto,
                    'parentesco'       => $this->normalizarValor('parentesco', $rawExcelData['parentesco'] ?? null),
                    'genero'           => $this->normalizarValor('genero', $rawExcelData['genero'] ?? null),
                    'correo'           => $this->normalizarEmail($rawExcelData['email'] ?? null),
                    'fecha_nacimiento' => trim($rawExcelData['fecha_nacimiento'] ?? ''),
                ];

                $erroresFila = $this->validarFilaFamiliar($mappedData, $filaNum);
                if (!empty($erroresFila)) {
                    $errores = array_merge($errores, $erroresFila);
                    $resumen['familiares']['errores']++;
                    continue;
                }

                $socio = $sociosDb->get($numAccion);
                if ($socio) {
                    $key = $socio->id_socio . '_' . mb_strtolower(trim($nombreCompleto));
                    $familiar = $familiaresLookup[$key] ?? null;
                    if (!$familiar) {
                        $fechaNacExcel = trim($mappedData['fecha_nacimiento']);
                        $familiaresToInsert[] = [
                            'socio_id'         => $socio->id_socio,
                            'nombre_completo'  => $nombreCompleto,
                            'parentesco'       => $mappedData['parentesco'] ?? 'OTRO',
                            'genero'           => $mappedData['genero'] ?? 'M',
                            'correo'           => $mappedData['correo'],
                            'fecha_nacimiento' => !empty($fechaNacExcel) ? ($this->normalizarFecha($fechaNacExcel) ?? '1995-01-01') : '1995-01-01',
                            'contador_no_shows'=> 0
                        ];
                    } else {
                        $updateData = $this->construirUpdateDataFamiliar($familiar, $mappedData);
                        if (empty($updateData)) {
                            $resumen['familiares']['sin_cambios']++;
                            continue;
                        }
                        $familiaresToUpdate[] = [$familiar, $updateData];
                    }
                } else if (isset($sociosToInsert[$numAccion])) {
                    $fechaNacExcel = trim($mappedData['fecha_nacimiento']);
                    $familiaresQueueNewPartners[$numAccion][] = [
                        'nombre_completo'  => $nombreCompleto,
                        'parentesco'       => $mappedData['parentesco'] ?? 'OTRO',
                        'genero'           => $mappedData['genero'] ?? 'M',
                        'correo'           => $mappedData['correo'],
                        'fecha_nacimiento' => !empty($fechaNacExcel) ? ($this->normalizarFecha($fechaNacExcel) ?? '1995-01-01') : '1995-01-01',
                        'contador_no_shows'=> 0
                    ];
                } else {
                    $errores[] = ['hoja' => 'Base_Socios', 'fila' => $filaNum, 'campo' => 'numero_accion', 'valor' => $numAccion, 'mensaje' => "No se encontró el titular para el familiar."];
                    $resumen['familiares']['errores']++;
                }
            }
        }

        if (!empty($errores)) {
            return ['success' => false, 'resumen' => $resumen, 'errores' => $errores, 'actualizaciones' => []];
        }

        DB::beginTransaction();
        try {
            foreach ($sociosToUpdate as $item) {
                list($socio, $updateData) = $item;
                $socio->update($updateData);
                $resumen['socios']['actualizados']++;
                $actualizaciones[] = ['hoja' => 'Base_Socios (Actualizado)', 'numero_accion' => $socio->numero_accion, 'nombre' => $socio->nombre_completo, 'campos_actualizados' => array_keys($updateData)];
            }
            foreach ($usersToUpdateEmail as $item) {
                list($socioId, $newEmail) = $item;
                User::where('user_id', $socioId)->where('rol', 'socio_titular')->update(['email' => $newEmail]);
            }
            foreach ($familiaresToUpdate as $item) {
                list($familiar, $updateData) = $item;
                $familiar->update($updateData);
                $resumen['familiares']['actualizados']++;
                $actualizaciones[] = ['hoja' => 'Base_Socios (Familiar Actualizado)', 'numero_accion' => $familiar->socio->numero_accion ?? '-', 'nombre' => $familiar->nombre_completo, 'campos_actualizados' => array_keys($updateData)];
            }
            if (!empty($sociosToInsert)) {
                foreach (array_chunk(array_values($sociosToInsert), 500) as $chunk) { SocioTitular::insert($chunk); }
                $insertedSocios = SocioTitular::whereIn('numero_accion', array_keys($sociosToInsert))->get(['id_socio', 'numero_accion'])->keyBy('numero_accion');
                $usersToInsert = [];
                $defaultHashedPassword = bcrypt('password'); // Pre-calcular el hash una sola vez fuera del bucle
                foreach ($insertedSocios as $numAccion => $socioModel) {
                    $partnerData = $sociosToInsert[$numAccion];
                    $usersToInsert[] = [
                        'user_id' => $socioModel->id_socio,
                        'email' => $partnerData['correo_electronico'],
                        'rol' => 'socio_titular',
                        'password' => $defaultHashedPassword,
                        'activo' => true
                    ];
                    $resumen['socios']['actualizados']++;
                    $actualizaciones[] = ['hoja' => 'Base_Socios (Nuevo)', 'numero_accion' => $numAccion, 'nombre' => $partnerData['nombre_completo'], 'campos_actualizados' => ['Todo (Insert Masivo)']];
                }
                foreach (array_chunk($usersToInsert, 500) as $chunk) { User::insert($chunk); }
                foreach ($familiaresQueueNewPartners as $numAccion => $famList) {
                    $socioModel = $insertedSocios->get($numAccion);
                    if ($socioModel) {
                        foreach ($famList as $famData) { $famData['socio_id'] = $socioModel->id_socio; $familiaresToInsert[] = $famData; }
                    }
                }
            }
            if (!empty($familiaresToInsert)) {
                foreach (array_chunk($familiaresToInsert, 500) as $chunk) { MiembrosFamiliares::insert($chunk); }
                foreach ($familiaresToInsert as $famData) {
                    $resumen['familiares']['actualizados']++;
                    $actualizaciones[] = ['hoja' => 'Base_Socios (Familiar Nuevo)', 'numero_accion' => '-', 'nombre' => $famData['nombre_completo'], 'campos_actualizados' => ['Todo (Insert Masivo)']];
                }
            }
            $maxSocioId = DB::table('socios_titulares')->max('id_socio') ?: 0;
            if ($maxSocioId > 0) DB::statement("SELECT setval('socios_titulares_id_socio_seq', $maxSocioId, true);");
            $maxFamiliarId = DB::table('miembros_familiares')->max('id_miembro') ?: 0;
            if ($maxFamiliarId > 0) DB::statement("SELECT setval('miembros_familiares_id_miembro_seq', $maxFamiliarId, true);");
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExcelImportController — Error en transacción atómica por lotes', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
            ]);
            return [
                'success' => false,
                'resumen' => [
                    'socios' => ['total' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => 0],
                    'familiares' => ['total' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => 0]
                ],
                'errores'         => [[
                    'hoja' => 'Base_Socios', 
                    'fila' => 0, 
                    'campo' => 'Error de Base de Datos', 
                    'valor' => '-', 
                    'mensaje' => 'Error crítico durante la actualización de la base de datos. Se revirtieron todos los cambios: ' . $e->getMessage()
                ]],
                'actualizaciones' => [],
            ];
        }

        return [
            'success' => true,
            'resumen' => $resumen,
            'errores' => $errores,
            'actualizaciones' => $actualizaciones
        ];
    }

    // ================================================================
    //  VALIDACIONES
    // ================================================================

    private function validarFilaSocio(array $data, int $fila): array
    {
        $errores = [];
        $hoja    = 'Socios';

        // nombre_completo
        if (isset($data['nombre_completo']) && mb_strlen($data['nombre_completo']) > self::MAX_NOMBRE) {
            $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'nombre_completo', 'valor' => mb_substr($data['nombre_completo'], 0, 20) . '…', 'mensaje' => 'Máximo ' . self::MAX_NOMBRE . ' caracteres.'];
        }

        // numero_accion
        if (isset($data['numero_accion']) && mb_strlen($data['numero_accion']) > self::MAX_NUM_ACCION) {
            $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'numero_accion', 'valor' => $data['numero_accion'], 'mensaje' => 'Máximo ' . self::MAX_NUM_ACCION . ' caracteres.'];
        }

        // tipo_socio
        if (!empty($data['tipo_socio'])) {
            $val = mb_strtoupper(trim($data['tipo_socio']));
            if (!in_array($val, self::ENUM_TIPO_SOCIO)) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'tipo_socio', 'valor' => $data['tipo_socio'], 'mensaje' => 'Valor no permitido. Use: ' . implode(', ', self::ENUM_TIPO_SOCIO)];
            }
        }

        // modalidad_plan
        if (!empty($data['modalidad_plan'])) {
            $val = mb_strtoupper(trim($data['modalidad_plan']));
            if (!in_array($val, self::ENUM_MODALIDAD_PLAN)) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'modalidad_plan', 'valor' => $data['modalidad_plan'], 'mensaje' => 'Valor no permitido. Use: ' . implode(', ', self::ENUM_MODALIDAD_PLAN)];
            }
        }

        // genero
        if (!empty($data['genero'])) {
            $val = mb_strtoupper(trim($data['genero']));
            if (!in_array($val, self::ENUM_GENERO)) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'genero', 'valor' => $data['genero'], 'mensaje' => 'Valor no permitido. Use: ' . implode(', ', self::ENUM_GENERO)];
            }
        }

        // estatus_cuenta
        if (!empty($data['estatus_cuenta'])) {
            $val = mb_strtoupper(trim($data['estatus_cuenta']));
            if (!in_array($val, self::ENUM_ESTATUS_CUENTA)) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'estatus_cuenta', 'valor' => $data['estatus_cuenta'], 'mensaje' => 'Valor no permitido. Use: ' . implode(', ', self::ENUM_ESTATUS_CUENTA)];
            }
        }

        // correo_electronico
        if (!empty($data['correo_electronico'])) {
            if (mb_strlen($data['correo_electronico']) > self::MAX_CORREO) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'correo_electronico', 'valor' => $data['correo_electronico'], 'mensaje' => 'Máximo ' . self::MAX_CORREO . ' caracteres.'];
            }
            if (!filter_var($data['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'correo_electronico', 'valor' => $data['correo_electronico'], 'mensaje' => 'Formato de correo electrónico inválido.'];
            }
        }

        // fecha_nacimiento
        if (!empty($data['fecha_nacimiento'])) {
            $fecha = $this->normalizarFecha($data['fecha_nacimiento']);
            if ($fecha === null) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'fecha_nacimiento', 'valor' => $data['fecha_nacimiento'], 'mensaje' => 'Formato de fecha inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];
            }
        }

        // fecha_afiliacion
        if (!empty($data['fecha_afiliacion'])) {
            $fecha = $this->normalizarFecha($data['fecha_afiliacion']);
            if ($fecha === null) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'fecha_afiliacion', 'valor' => $data['fecha_afiliacion'], 'mensaje' => 'Formato de fecha inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];
            }
        }

        return $errores;
    }

    private function validarFilaFamiliar(array $data, int $fila): array
    {
        $errores = [];
        $hoja    = 'Familiares';

        // nombre_completo
        if (isset($data['nombre_completo']) && mb_strlen($data['nombre_completo']) > self::MAX_NOMBRE) {
            $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'nombre_completo', 'valor' => mb_substr($data['nombre_completo'], 0, 20) . '…', 'mensaje' => 'Máximo ' . self::MAX_NOMBRE . ' caracteres.'];
        }

        // parentesco
        if (!empty($data['parentesco'])) {
            $val = mb_strtoupper(trim($data['parentesco']));
            if (!in_array($val, self::ENUM_PARENTESCO)) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'parentesco', 'valor' => $data['parentesco'], 'mensaje' => 'Valor no permitido. Use: ' . implode(', ', self::ENUM_PARENTESCO)];
            }
        }

        // genero
        if (!empty($data['genero'])) {
            $val = mb_strtoupper(trim($data['genero']));
            if (!in_array($val, self::ENUM_GENERO)) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'genero', 'valor' => $data['genero'], 'mensaje' => 'Valor no permitido. Use: ' . implode(', ', self::ENUM_GENERO)];
            }
        }

        // correo
        if (!empty($data['correo'])) {
            if (mb_strlen($data['correo']) > self::MAX_CORREO_FAM) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'correo', 'valor' => $data['correo'], 'mensaje' => 'Máximo ' . self::MAX_CORREO_FAM . ' caracteres.'];
            }
            if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'correo', 'valor' => $data['correo'], 'mensaje' => 'Formato de correo electrónico inválido.'];
            }
        }

        // fecha_nacimiento
        if (!empty($data['fecha_nacimiento'])) {
            $fecha = $this->normalizarFecha($data['fecha_nacimiento']);
            if ($fecha === null) {
                $errores[] = ['hoja' => $hoja, 'fila' => $fila, 'campo' => 'fecha_nacimiento', 'valor' => $data['fecha_nacimiento'], 'mensaje' => 'Formato de fecha inválido. Use YYYY-MM-DD o DD/MM/YYYY.'];
            }
        }

        return $errores;
    }

    // ================================================================
    //  CONSTRUCCIÓN DE UPDATE DATA
    // ================================================================

    private function construirUpdateDataSocio(SocioTitular $socio, array $data): array
    {
        $updateData = [];

        $camposTexto = ['nombre_completo', 'correo_electronico'];
        foreach ($camposTexto as $campo) {
            if (isset($data[$campo]) && trim($data[$campo]) !== '') {
                $newVal = trim($data[$campo]);
                if ($socio->{$campo} !== $newVal) {
                    $updateData[$campo] = $newVal;
                }
            }
        }

        // Enums (normalizar a UPPER)
        $camposEnum = ['tipo_socio', 'modalidad_plan', 'genero', 'estatus_cuenta'];
        foreach ($camposEnum as $campo) {
            if (isset($data[$campo]) && trim($data[$campo]) !== '') {
                $newVal = mb_strtoupper(trim($data[$campo]));
                if ($socio->{$campo} !== $newVal) {
                    $updateData[$campo] = $newVal;
                }
            }
        }

        // Fechas
        $camposFecha = ['fecha_nacimiento', 'fecha_afiliacion'];
        foreach ($camposFecha as $campo) {
            if (isset($data[$campo]) && trim((string) $data[$campo]) !== '') {
                $newVal = $this->normalizarFecha($data[$campo]);
                if ($newVal !== null) {
                    $currentVal = $socio->{$campo} ? $socio->{$campo}->format('Y-m-d') : null;
                    if ($currentVal !== $newVal) {
                        $updateData[$campo] = $newVal;
                    }
                }
            }
        }

        return $updateData;
    }

    private function construirUpdateDataFamiliar(MiembrosFamiliares $familiar, array $data): array
    {
        $updateData = [];

        // Texto
        if (isset($data['correo']) && trim($data['correo']) !== '') {
            $newVal = trim($data['correo']);
            if ($familiar->correo !== $newVal) {
                $updateData['correo'] = $newVal;
            }
        }

        // Enums
        if (isset($data['parentesco']) && trim($data['parentesco']) !== '') {
            $newVal = mb_strtoupper(trim($data['parentesco']));
            if ($familiar->parentesco !== $newVal) {
                $updateData['parentesco'] = $newVal;
            }
        }
        if (isset($data['genero']) && trim($data['genero']) !== '') {
            $newVal = mb_strtoupper(trim($data['genero']));
            if ($familiar->genero !== $newVal) {
                $updateData['genero'] = $newVal;
            }
        }

        // Fechas
        if (isset($data['fecha_nacimiento']) && trim((string) $data['fecha_nacimiento']) !== '') {
            $newVal = $this->normalizarFecha($data['fecha_nacimiento']);
            if ($newVal !== null) {
                $currentVal = $familiar->fecha_nacimiento ? $familiar->fecha_nacimiento->format('Y-m-d') : null;
                if ($currentVal !== $newVal) {
                    $updateData['fecha_nacimiento'] = $newVal;
                }
            }
        }

        return $updateData;
    }

    // ================================================================
    //  UTILIDADES
    // ================================================================

    /**
     * Mapea los encabezados del Excel a las columnas esperadas.
     * Busca coincidencias case-insensitive y sin acentos.
     */
    private function mapearEncabezados(array $headerRow, array $expectedColumns): array
    {
        $map = [];
        $normalizedExpected = [];
        foreach ($expectedColumns as $col) {
            $normalizedExpected[$this->normalizeHeaderName($col)] = $col;
        }

        foreach ($headerRow as $colLetter => $headerValue) {
            if (empty($headerValue)) continue;
            $normalized = $this->normalizeHeaderName((string) $headerValue);
            if (isset($normalizedExpected[$normalized])) {
                $map[$normalizedExpected[$normalized]] = $colLetter;
            }
        }

        return $map;
    }

    /**
     * Normaliza un nombre de encabezado: minúsculas, sin acentos, reemplaza
     * espacios/guiones por guión bajo.
     */
    private function normalizeHeaderName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        // Remover acentos comunes
        $accents = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ];
        $name = strtr($name, $accents);
        // Reemplazar espacios y guiones por _
        $name = preg_replace('/[\s\-]+/', '_', $name);
        // Remover caracteres no alfanuméricos excepto _ y /
        $name = preg_replace('/[^a-z0-9_\/]/', '', $name);
        return $name;
    }

    /**
     * Extrae los valores de una fila según el mapa de columnas.
     */
    private function extraerValoresMapeados(array $row, array $columnMap): array
    {
        $data = [];
        foreach ($columnMap as $fieldName => $colLetter) {
            $data[$fieldName] = isset($row[$colLetter]) ? trim((string) $row[$colLetter]) : '';
        }
        return $data;
    }

    /**
     * Normaliza una fecha a formato Y-m-d.
     * Acepta: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, números seriales de Excel.
     * Retorna null si no se puede parsear.
     */
    private function normalizarFecha($value): ?string
    {
        if (empty($value)) return null;

        $value = trim((string) $value);

        // Número serial de Excel
        if (is_numeric($value) && (int) $value > 30000 && (int) $value < 100000) {
            try {
                $dateTime = ExcelDate::excelToDateTimeObject((float) $value);
                return $dateTime->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $d = \DateTime::createFromFormat('Y-m-d', $value);
            return ($d && $d->format('Y-m-d') === $value) ? $value : null;
        }

        // DD/MM/YYYY
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            $d = \DateTime::createFromFormat('d/m/Y', $value);
            return $d ? $d->format('Y-m-d') : null;
        }

        // DD-MM-YYYY
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
            $d = \DateTime::createFromFormat('d-m-Y', $value);
            return $d ? $d->format('Y-m-d') : null;
        }

        return null;
    }
}
