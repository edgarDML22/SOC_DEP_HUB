<?php
/**
 * scratch_fix_excel_v2.php
 *
 * Replica EXACTAMENTE la lógica del backend (ExcelImportController)
 * para detectar filas con email duplicado, y las corrige en el Excel.
 *
 * Offset corregido: backend usa ($rowIndex + 1) donde $rowIndex es el índice
 * 0-based DESPUÉS del array_shift. Por tanto, la celda real en Excel = filaNum + 1.
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$excelPath = __DIR__ . '/../Base_Datos_Socios_Club_Morelia.xlsx';

if (!file_exists($excelPath)) {
    die("ERROR: No se encontró el archivo en: $excelPath\n");
}

echo "=== FIX EXCEL v2 — Usando lógica exacta del backend ===\n\n";

// ── Funciones idénticas al backend ─────────────────────────────────
function normalizarEmail(?string $email): ?string {
    if ($email === null || trim($email) === '') return null;
    $email = mb_strtolower(trim($email));
    $accents = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'];
    $email = strtr($email, $accents);
    return preg_replace('/\s+/', '', $email);
}

function normalizeHeaderName(string $name): string {
    $name = mb_strtolower(trim($name));
    $accents = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'];
    $name = strtr($name, $accents);
    $name = preg_replace('/[\s\-]+/', '_', $name);
    $name = preg_replace('/[^a-z0-9_\/]/', '', $name);
    return $name;
}

// ── 1. Cargar BD ────────────────────────────────────────────────────
$usersDbEmails = DB::table('users')
    ->where('rol', 'socio_titular')
    ->pluck('user_id', 'email')
    ->mapWithKeys(fn($userId, $email) => [mb_strtolower(trim($email)) => $userId])
    ->toArray();

$sociosDb = DB::table('socios_titulares')
    ->select('id_socio', 'numero_accion')
    ->get()
    ->keyBy('numero_accion');

echo "Socios en BD: " . $sociosDb->count() . "\n";
echo "Emails en BD: " . count($usersDbEmails) . "\n\n";

// ── 2. Cargar Excel ─────────────────────────────────────────────────
$spreadsheet = IOFactory::load($excelPath);
$sheet       = $spreadsheet->getSheet(0);
$rows        = $sheet->toArray(null, true, true, true);

// array_shift re-indexa el array a 0-based (igual que en el backend)
$headerRow = array_shift($rows);

// Mapear columnas
$columnMap = [];
foreach ($headerRow as $colLetter => $headerValue) {
    if (empty($headerValue)) continue;
    $normalized = normalizeHeaderName((string)$headerValue);
    $columnMap[$normalized] = $colLetter;
}

$colEmail  = $columnMap['email']         ?? null;
$colAccion = $columnMap['numero_accion'] ?? null;
$colRol    = $columnMap['rol']           ?? null;

if (!$colEmail || !$colAccion || !$colRol) {
    die("ERROR: Columnas no encontradas. Mapa: " . print_r($columnMap, true) . "\n");
}

echo "Columnas → accion: $colAccion | rol: $colRol | email: $colEmail\n";
echo "Total filas de datos: " . count($rows) . "\n\n";

// ── 3. Bucle EXACTO al del backend ──────────────────────────────────
// Después del array_shift, las claves son 0-based (0, 1, 2...)
// $rowIndex en backend = clave 0-based
// $filaNum en backend  = $rowIndex + 1
// Celda real en Excel  = $filaNum + 1  (porque la cabecera estaba en fila 1 del Excel,
//                                       y los datos en fila 2 en adelante)

$seenEmailsInImport = [];
$fixMap = [];  // filaNum_backend => ['col' => colLetter, 'excelRow' => N, 'emailOriginal' => ..., 'razon' => ...]

foreach ($rows as $rowIndex => $row) {
    $filaNum = $rowIndex + 1;  // EXACTO al backend

    $rol = mb_strtolower(trim($row[$colRol] ?? ''));
    if (empty($rol)) continue;

    $numAccion = trim($row[$colAccion] ?? '');
    if (empty($numAccion)) continue;

    if ($rol !== 'titular') continue;  // solo titulares tienen user/email

    $emailRaw  = trim($row[$colEmail] ?? '');
    $emailNorm = normalizarEmail($emailRaw);

    if ($emailNorm === null) continue;

    $socio = $sociosDb->get($numAccion);

    if (!$socio) {
        // Socio NUEVO — misma lógica que el backend
        $conflicto = null;

        if (isset($usersDbEmails[$emailNorm])) {
            $conflicto = "Email ya en BD (user_id: {$usersDbEmails[$emailNorm]})";
        } elseif (isset($seenEmailsInImport[$emailNorm])) {
            $conflicto = "Duplicado en Excel con fila {$seenEmailsInImport[$emailNorm]}";
        }

        if ($conflicto) {
            // La celda real en Excel es $filaNum + 1
            $excelRow = $filaNum + 1;
            $fixMap[$filaNum] = [
                'excelRow'      => $excelRow,
                'col'           => $colEmail,
                'emailOriginal' => $emailRaw,
                'razon'         => $conflicto,
                'accion'        => $numAccion,
            ];
            // NO agregamos a seenEmailsInImport (el email de esta fila se borrará)
        } else {
            $seenEmailsInImport[$emailNorm] = $filaNum;
        }
    }
    // Si el socio ES EXISTENTE (update), no tocamos su email (es válido en BD)
}

// ── 4. Reporte ──────────────────────────────────────────────────────
echo "=== CONFLICTOS DETECTADOS: " . count($fixMap) . " ===\n\n";

if (empty($fixMap)) {
    echo "✅ No se encontraron conflictos. El Excel ya está limpio.\n";
    exit(0);
}

foreach ($fixMap as $filaBackend => $info) {
    printf(
        "Backend Fila %4d → Excel fila %4d | acción=%-6s | email=%-45s | motivo=%s\n",
        $filaBackend,
        $info['excelRow'],
        $info['accion'],
        $info['emailOriginal'],
        $info['razon']
    );
}

// ── 5. Aplicar correcciones ─────────────────────────────────────────
echo "\n=== APLICANDO CORRECCIONES ===\n";

foreach ($fixMap as $filaBackend => $info) {
    $cellAddr = $info['col'] . $info['excelRow'];
    $sheet->setCellValue($cellAddr, '');
}

// ── 6. Guardar ─────────────────────────────────────────────────────
$backupPath = $excelPath . '.backup2_' . date('Ymd_His') . '.xlsx';
copy($excelPath, $backupPath);
echo "Backup guardado: $backupPath\n";

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save($excelPath);

echo "✅ Excel guardado con " . count($fixMap) . " emails vaciados.\n";
echo "\nPuedes importar de nuevo desde el panel admin.\n";
