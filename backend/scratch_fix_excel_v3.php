<?php
/**
 * scratch_fix_excel_v3.php
 *
 * ANÁLISIS DEFINITIVO (basado en la inspección):
 *
 * $sheet->toArray(null, true, true, TRUE) con returnCellRef=true devuelve claves
 * 1-based (fila 1 = header, fila 2 = primer dato, etc.) y NO las re-indexa.
 *
 * El backend hace array_shift() → en PHP, array_shift en un array con claves
 * numéricas SÍ re-indexa a 0-based. Así que después del shift:
 *   - $rows[0] = fila 2 del Excel (primer dato)
 *   - $rows[1] = fila 3, etc.
 *
 * backend $filaNum = $rowIndex + 1 donde $rowIndex es 0-based.
 * → backend fila N = Excel fila N+1
 *
 * PERO: la inspección mostró que Excel row=723 (accion=1321, titular) tiene
 * email limpio, mientras Excel row=724 (accion=1322) tiene email=??? (vacío).
 * El backend dice "Fila 723" para el error de email de accion=1322.
 *
 * Esto significa: backend "Fila 723" corresponde al rowIndex=722 (0-based),
 * que era originalmente Excel fila 723 (antes del shift, ese elemento tenía clave 723,
 * que tras re-indexación queda en índice 722).
 * Excel fila 723 (clave original 723 = índice 0-based 722, filaNum = 723).
 *
 * PERO la inspección mostró que Excel row=723 (accedido con clave 723 sin shift)
 * tiene email de accion=1321 (un titular diferente), no de accion=1322.
 *
 * CONCLUSIÓN: El v1 vació la celda CORRECTA (Excel row 724) pero el backend
 * reportó la fila 723 (que es la fila del titular 1322 con el email duplicado,
 * que en mi acceso sin shift corresponde a Excel key=724 pero el backend (con shift
 * y re-indexación) lo ve como índice 722, filaNum=723).
 *
 * ENTONCES: el v1 SÍ vació las celdas correctas (72 emails).
 * El problema es que el EXCEL TIENE ACENTOS en los emails y el backend normaliza
 * quitando acentos. Al quitar acentos, emails con acento = sin acento → duplicados.
 *
 * La raíz real: el v1 vació filas encontradas por comparación normalizada, PERO
 * no encontró TODAS las filas porque algunos emails en la primera fila también
 * tienen acentos (y no se encontraron como ya-vistos porque la comparación es distinta).
 *
 * SOLUCIÓN FINAL: Recorrer el Excel con el mismo índice del backend (usando array_shift
 * que sí re-indexa) y detectar/vaciar duplicados, luego usar la clave 1-based original
 * (que guardamos antes del shift) para hacer el setCellValue correcto.
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$excelPath = __DIR__ . '/../Base_Datos_Socios_Club_Morelia.xlsx';

if (!file_exists($excelPath)) {
    die("ERROR: $excelPath no encontrado\n");
}

echo "=== FIX EXCEL v3 — Solución definitiva ===\n\n";

function normalizarEmail(?string $email): ?string {
    if ($email === null || trim($email) === '') return null;
    $email = mb_strtolower(trim($email));
    $accents = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'];
    $email = strtr($email, $accents);
    return preg_replace('/\s+/', '', $email);
}

function normalizeHeader(string $name): string {
    $name = mb_strtolower(trim($name));
    $accents = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'];
    $name = strtr($name, $accents);
    $name = preg_replace('/[\s\-]+/', '_', $name);
    return preg_replace('/[^a-z0-9_\/]/', '', $name);
}

// ── BD ──────────────────────────────────────────────────────────────
$usersDbEmails = DB::table('users')
    ->where('rol', 'socio_titular')
    ->pluck('user_id', 'email')
    ->mapWithKeys(fn($uid, $em) => [normalizarEmail($em) => $uid])
    ->toArray();

$sociosDb = DB::table('socios_titulares')
    ->pluck('id_socio', 'numero_accion')
    ->toArray();

echo "Socios BD: " . count($sociosDb) . " | Emails BD: " . count($usersDbEmails) . "\n\n";

// ── Cargar Excel manteniendo claves 1-based ─────────────────────────
$spreadsheet = IOFactory::load($excelPath);
$sheet       = $spreadsheet->getSheet(0);

// Con returnCellRef=true, el array viene con claves 1-based (fila 1 = header)
$rowsWithKeys = $sheet->toArray(null, true, true, true);

// Guardamos un mapa: índice-0-based → clave-excel-1-based
// porque después del array_shift el backend trabaja con índices 0-based
$excelKeyMap = []; // 0-based-idx => 1-based-excel-row
$rowsForProcessing = []; // ordered array (0-based) of rows, after removing header

$i = 0;
foreach ($rowsWithKeys as $excelRow => $row) {
    if ($excelRow == 1) {
        // Header → mapear columnas
        $headerRow = $row;
        continue;
    }
    $excelKeyMap[$i] = $excelRow;
    $rowsForProcessing[$i] = $row;
    $i++;
}

// Mapear columnas
$columnMap = [];
foreach ($headerRow as $colLetter => $val) {
    if (empty($val)) continue;
    $columnMap[normalizeHeader((string)$val)] = $colLetter;
}

$colEmail  = $columnMap['email']         ?? null;
$colAccion = $columnMap['numero_accion'] ?? null;
$colRol    = $columnMap['rol']           ?? null;

if (!$colEmail || !$colAccion || !$colRol) {
    die("No se encontraron columnas. Mapa: " . json_encode($columnMap) . "\n");
}

echo "Columnas: accion=$colAccion | rol=$colRol | email=$colEmail\n";
echo "Datos: " . count($rowsForProcessing) . " filas\n\n";

// ── Bucle idéntico al backend ────────────────────────────────────────
// Ahora $rowIndex es el índice 0-based (exacto al del backend post-shift)
$seenEmailsInImport = [];
$fixes = []; // excelRow => email_original

foreach ($rowsForProcessing as $rowIndex => $row) {
    $filaNum = $rowIndex + 1; // == backend's $filaNum

    $rol = mb_strtolower(trim($row[$colRol] ?? ''));
    if (empty($rol) || $rol !== 'titular') continue;

    $numAccion = trim($row[$colAccion] ?? '');
    if (empty($numAccion)) continue;

    $emailRaw  = trim($row[$colEmail] ?? '');
    $emailNorm = normalizarEmail($emailRaw);
    if ($emailNorm === null) continue;

    // Si el socio NO existe en BD → es nuevo
    if (!isset($sociosDb[$numAccion])) {
        $conflicto = false;

        if (isset($usersDbEmails[$emailNorm])) {
            echo "  → Fila $filaNum (Excel " . ($rowIndex+2) . "): email ya en BD (user_id={$usersDbEmails[$emailNorm]}): $emailRaw\n";
            $conflicto = true;
        } elseif (isset($seenEmailsInImport[$emailNorm])) {
            echo "  → Fila $filaNum (Excel " . ($rowIndex+2) . "): duplicado con fila {$seenEmailsInImport[$emailNorm]}: $emailRaw\n";
            $conflicto = true;
        }

        if ($conflicto) {
            // La clave 1-based real del Excel para este row
            $excelRow = $excelKeyMap[$rowIndex];
            $fixes[$excelRow] = $emailRaw;
        } else {
            $seenEmailsInImport[$emailNorm] = $filaNum;
        }
    }
}

echo "\n=== CONFLICTOS: " . count($fixes) . " ===\n\n";

if (empty($fixes)) {
    echo "✅ El Excel está limpio — no hay nada que corregir.\n";
    echo "   Posible causa del error: la importación puede estar usando el archivo original (sin guardar).\n";
    echo "   Archivos en carpeta raíz:\n";
    foreach (glob(dirname($excelPath) . '/*.xlsx') as $f) {
        echo "   - " . basename($f) . " (" . date('Y-m-d H:i:s', filemtime($f)) . ")\n";
    }
    exit(0);
}

// ── Aplicar ─────────────────────────────────────────────────────────
foreach ($fixes as $excelRow => $emailOrig) {
    $cell = $colEmail . $excelRow;
    $sheet->setCellValue($cell, '');
    echo "  Vaciando celda $cell (tenía: $emailOrig)\n";
}

$backupPath = $excelPath . '.v3backup_' . date('Ymd_His') . '.xlsx';
copy($excelPath, $backupPath);
echo "\nBackup: $backupPath\n";

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save($excelPath);
echo "✅ Guardado. " . count($fixes) . " emails vaciados.\n";
