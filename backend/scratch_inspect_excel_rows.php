<?php
/**
 * scratch_inspect_excel_rows.php
 * Muestra el contenido exacto de las filas que el backend reporta como error.
 * Backend "Fila N" = Excel row (N+1) en el array re-indexado post-shift.
 * 
 * Verificamos ambas fórmulas y mostramos qué hay en esas celdas exactas.
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$excelPath = __DIR__ . '/../Base_Datos_Socios_Club_Morelia.xlsx';

$spreadsheet = IOFactory::load($excelPath);
$sheet       = $spreadsheet->getSheet(0);

// Filas reportadas por el backend como error (las primeras del mensaje)
// Formato: [backend_fila, descripcion]
$errorFilas = [
    723  => 'email duplicado (también en 521)',
    827  => 'email duplicado (también en 268)',
    868  => 'email duplicado (también en 229)',
    966  => 'email duplicado (también en 894)',
    986  => 'email ya en BD (socio 8)',
    988  => 'email duplicado (también en 579)',
    1072 => 'email duplicado (también en 427)',
    1139 => 'email duplicado (también en 926)',
    1171 => 'email duplicado (también en 416)',
    1202 => 'email duplicado (también en 44)',
    1207 => 'email duplicado (también en 1027)',
    1240 => 'email duplicado (también en 567)',
    1244 => 'email duplicado (también en 204)',
];

// Columna K = email (según v2 detectó)
$colEmail  = 'K';
$colAccion = 'A';
$colRol    = 'D';

// Leer sheet completo con row reference
$allRows = $sheet->toArray(null, true, true, true);
// Con returnCellRef=true, las claves son 1-based Excel row numbers (NO se re-indexan)

echo "=== INSPECCIÓN DE CELDAS ===\n\n";
echo "Total filas en array: " . count($allRows) . "\n";
echo "Primera clave: " . array_key_first($allRows) . "\n";
echo "Última clave:  " . array_key_last($allRows) . "\n\n";

// Verificar cuál es la fila 1 (header)
$headerRow = $allRows[1] ?? null;
if ($headerRow) {
    echo "Fila 1 (header): " . implode(' | ', array_filter($headerRow)) . "\n\n";
}

// Mostrar contenido en distintas interpretaciones de fila
echo str_pad("BackendFila", 12) . str_pad("ExcelRow=N", 12) . str_pad("ExcelRow=N+1", 14) . str_pad("Accion@N", 15) . str_pad("Rol@N", 8) . "Email@N\n";
echo str_repeat('-', 100) . "\n";

foreach ($errorFilas as $backendFila => $desc) {
    // Interpretación 1: Excel row = backend_fila (directo)
    $row1 = $allRows[$backendFila] ?? null;
    // Interpretación 2: Excel row = backend_fila + 1
    $row2 = $allRows[$backendFila + 1] ?? null;

    $accion1 = trim($row1[$colAccion] ?? '???');
    $rol1    = trim($row1[$colRol]    ?? '???');
    $email1  = trim($row1[$colEmail]  ?? '???');

    $accion2 = trim($row2[$colAccion] ?? '???');
    $rol2    = trim($row2[$colRol]    ?? '???');
    $email2  = trim($row2[$colEmail]  ?? '???');

    echo "Fila $backendFila:\n";
    printf("  Excel row=%-4d → accion=%-6s rol=%-8s email=%s\n", $backendFila, $accion1, $rol1, $email1);
    printf("  Excel row=%-4d → accion=%-6s rol=%-8s email=%s\n", $backendFila + 1, $accion2, $rol2, $email2);
    echo "  Descripción: $desc\n\n";
}

// También mostrar cuántas filas están vacías en columna K (email)
$vacias = 0;
foreach ($allRows as $rowNum => $row) {
    if ($rowNum == 1) continue; // skip header
    $email = trim($row[$colEmail] ?? '');
    if ($email === '') $vacias++;
}
echo "Total filas con email vacío (col K): $vacias\n";
