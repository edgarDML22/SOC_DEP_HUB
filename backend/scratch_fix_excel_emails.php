<?php
/**
 * scratch_fix_excel_emails.php
 *
 * Compara el Excel Base_Datos_Socios_Club_Morelia.xlsx contra la BD,
 * detecta correos conflictivos y los limpia directamente en el archivo.
 *
 * Estrategia de limpieza:
 *   1. Si el socio (por numero_accion) YA existe en la BD → usar el email
 *      que ya tiene en la BD (descartar el del Excel si es distinto y conflictivo).
 *   2. Si el email aparece duplicado DENTRO del Excel entre dos accion distintos
 *      → conservar el de la primera aparición, vaciar el de las posteriores.
 *   3. Si el email ya existe en la BD para OTRO socio distinto → vaciarlo en el Excel.
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$excelPath = __DIR__ . '/../Base_Datos_Socios_Club_Morelia.xlsx';

if (!file_exists($excelPath)) {
    die("ERROR: No se encontró el archivo en: $excelPath\n");
}

echo "=== ANALIZANDO EXCEL vs BASE DE DATOS ===\n";
echo "Archivo: $excelPath\n\n";

// ── 1. Cargar el Excel ──────────────────────────────────────────────
$spreadsheet = IOFactory::load($excelPath);
$sheet = $spreadsheet->getSheet(0);
$rows  = $sheet->toArray(null, true, true, true); // keyed by letter

// Detectar encabezados
$headerRow  = array_shift($rows);
$columnMap  = [];
foreach ($headerRow as $col => $val) {
    if (empty($val)) continue;
    $key = mb_strtolower(trim((string)$val));
    $key = strtr($key, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
    $key = preg_replace('/[\s\-]+/', '_', $key);
    $columnMap[$key] = $col;
}

$colEmail  = $columnMap['email']          ?? null;
$colAccion = $columnMap['numero_accion']  ?? null;
$colRol    = $columnMap['rol']            ?? null;

if (!$colEmail || !$colAccion || !$colRol) {
    die("ERROR: No se encontraron columnas necesarias. Mapa: " . print_r($columnMap, true) . "\n");
}

echo "Columnas detectadas → numero_accion: $colAccion | rol: $colRol | email: $colEmail\n";
echo "Total filas de datos: " . count($rows) . "\n\n";

// ── 2. Cargar BD: emails existentes en users (socios titulares) ─────
$usersDb = DB::table('users')
    ->where('rol', 'socio_titular')
    ->select('user_id', 'email')
    ->get();

// Map email_lower → user_id
$emailToUserId = [];
foreach ($usersDb as $u) {
    $emailToUserId[mb_strtolower(trim($u->email))] = $u->user_id;
}

// Map socio id → email actual
$sociosDb = DB::table('socios_titulares')
    ->select('id_socio', 'numero_accion', 'correo_electronico')
    ->get();

$accionToSocio = [];
foreach ($sociosDb as $s) {
    $accionToSocio[trim($s->numero_accion)] = $s;
}

echo "Socios en BD: " . count($accionToSocio) . "\n";
echo "Emails en BD (users): " . count($emailToUserId) . "\n\n";

// ── 3. Primer pase: analizar conflictos ────────────────────────────
$seenEmailsInExcel = []; // emailNorm → filaNum
$conflictos = [];         // filaNum => ['tipo', 'descripcion', 'accion', 'email_original', 'email_nuevo']

// Calcular fila real en el sheet (header era fila 1)
$excelRowOffset = 2; // fila 2 = primera de datos

$rowKeys = array_keys($rows);
foreach ($rowKeys as $idx => $rowKey) {
    $row     = $rows[$rowKey];
    $filaNum = $idx + $excelRowOffset; // fila real en el Excel

    $rol     = mb_strtolower(trim($row[$colRol] ?? ''));
    if ($rol !== 'titular') continue; // solo validar titulares (los miembros no tienen user)

    $numAccion   = trim($row[$colAccion] ?? '');
    $emailRaw    = trim($row[$colEmail]  ?? '');
    $emailNorm   = mb_strtolower($emailRaw);
    $emailNorm   = strtr($emailNorm, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
    $emailNorm   = preg_replace('/\s+/', '', $emailNorm);

    if (empty($emailNorm)) continue;

    $nuevoEmail = null;
    $tipo       = null;
    $desc       = null;

    // Caso A: El socio ya existe en la BD
    if (isset($accionToSocio[$numAccion])) {
        $socio = $accionToSocio[$numAccion];
        $emailBd = mb_strtolower(trim($socio->correo_electronico ?? ''));

        // Si el email del Excel es distinto al de BD y además conflictivo
        if ($emailBd && $emailBd !== $emailNorm) {
            // El email del Excel entra en conflicto? (ya existe en BD para otro)
            if (isset($emailToUserId[$emailNorm]) && $emailToUserId[$emailNorm] != $socio->id_socio) {
                $nuevoEmail = $socio->correo_electronico; // usar el de la BD
                $tipo = 'A_BD_CONFLICT';
                $desc = "Email del Excel ya pertenece a otro socio (ID: {$emailToUserId[$emailNorm]}). Se restaura email de BD.";
            }
        }
        // Si el email del Excel ya fue visto antes en el lote (duplicado dentro del excel, para esta accion que ya existe en bd)
        if ($nuevoEmail === null && isset($seenEmailsInExcel[$emailNorm]) && $seenEmailsInExcel[$emailNorm]['accion'] !== $numAccion) {
            // otro numero_accion ya usa este email → vaciar
            $nuevoEmail = '';
            $tipo = 'A_DUP_LOTE';
            $desc = "Email duplicado en Excel con fila {$seenEmailsInExcel[$emailNorm]['fila']} (acción {$seenEmailsInExcel[$emailNorm]['accion']}). Se vacía.";
        }
    } else {
        // Caso B: Socio nuevo (no existe en BD)
        // Email ya existe en BD para alguien más → vaciar
        if (isset($emailToUserId[$emailNorm])) {
            $nuevoEmail = '';
            $tipo = 'B_BD_EXISTS';
            $desc = "Socio nuevo pero email ya existe en BD (user_id: {$emailToUserId[$emailNorm]}). Se vacía.";
        }
        // Email duplicado dentro del mismo Excel (otro numero_accion)
        elseif (isset($seenEmailsInExcel[$emailNorm]) && $seenEmailsInExcel[$emailNorm]['accion'] !== $numAccion) {
            $nuevoEmail = '';
            $tipo = 'B_DUP_LOTE';
            $desc = "Email duplicado en Excel con fila {$seenEmailsInExcel[$emailNorm]['fila']} (acción {$seenEmailsInExcel[$emailNorm]['accion']}). Se vacía.";
        }
    }

    if ($nuevoEmail !== null) {
        $conflictos[$filaNum] = [
            'tipo'           => $tipo,
            'descripcion'    => $desc,
            'accion'         => $numAccion,
            'email_original' => $emailRaw,
            'email_nuevo'    => $nuevoEmail,
            'col_email'      => $colEmail,
            'row_key'        => $rowKey,
        ];
    }

    // Registrar en seenEmails solo si no tiene conflicto (o si ya se va a vaciar, no registrar)
    if ($nuevoEmail === null || $nuevoEmail !== '') {
        // Registrar con el email que tendrá después de la corrección
        $emailRegistrar = $nuevoEmail ?? $emailNorm;
        if ($emailRegistrar !== '') {
            if (!isset($seenEmailsInExcel[$emailRegistrar])) {
                $seenEmailsInExcel[$emailRegistrar] = ['fila' => $filaNum, 'accion' => $numAccion];
            }
        }
    } else {
        // Se va a vaciar, registrar el original del primer dueño
        if (!isset($seenEmailsInExcel[$emailNorm])) {
            $seenEmailsInExcel[$emailNorm] = ['fila' => $filaNum, 'accion' => $numAccion];
        }
    }
}

// ── 4. Reporte de conflictos encontrados ───────────────────────────
echo "=== CONFLICTOS DETECTADOS: " . count($conflictos) . " ===\n\n";
if (empty($conflictos)) {
    echo "✅ No se encontraron conflictos. El Excel está limpio.\n";
    exit(0);
}

$byTipo = [];
foreach ($conflictos as $fila => $c) {
    $byTipo[$c['tipo']][] = $fila;
    printf(
        "Fila %4d | %-15s | acción=%-6s | email_original=%-45s | email_nuevo=%s\n",
        $fila,
        $c['tipo'],
        $c['accion'],
        $c['email_original'],
        $c['email_nuevo'] === '' ? '[VACÍO]' : $c['email_nuevo']
    );
}

echo "\nResumen por tipo:\n";
foreach ($byTipo as $tipo => $filas) {
    echo "  $tipo: " . count($filas) . " filas → " . implode(', ', array_slice($filas, 0, 5)) . (count($filas) > 5 ? '...' : '') . "\n";
}

// ── 5. Aplicar correcciones al Excel ──────────────────────────────
echo "\n=== APLICANDO CORRECCIONES AL EXCEL ===\n";

// Necesitamos la celda real en el sheet (PhpSpreadsheet trabaja 1-indexed)
// header = fila 1, datos empiezan en fila 2
// rowKey en toArray es el índice 0-based de filas de datos
// fila real = rowKey + 2

$highestRow = $sheet->getHighestRow();

foreach ($conflictos as $filaNum => $c) {
    // filaNum es la fila real del Excel (1-indexed, con header en 1)
    $cell = $c['col_email'] . $filaNum;
    $sheet->setCellValue($cell, $c['email_nuevo']);
}

// ── 6. Guardar el Excel modificado ────────────────────────────────
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

// Guardar como backup primero
$backupPath = $excelPath . '.backup_' . date('Ymd_His') . '.xlsx';
copy($excelPath, $backupPath);
echo "Backup guardado en: $backupPath\n";

$writer->save($excelPath);
echo "✅ Excel actualizado: $excelPath\n";
echo "   → " . count($conflictos) . " emails corregidos.\n\n";

echo "=== LISTO ===\n";
echo "Puedes volver a intentar la importación desde el panel admin.\n";
