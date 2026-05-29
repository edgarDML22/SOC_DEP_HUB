<?php
/**
 * scratch_fix_excel_v4.php
 *
 * PROBLEMA REAL: El Excel tiene emails con y sin acentos que normalizan igual.
 * El v1/v3 no los detectó porque usa la misma normalización en ambos lados.
 *
 * NUEVA ESTRATEGIA: Recorrer el archivo exactamente como lo hace el backend
 * (mismo orden, misma normalización) y detectar/vaciar duplicados directamente
 * sobre el objeto spreadsheet, usando el mapa 0-based → 1-based excel row.
 *
 * La clave estaba en que el v3 reportó 0 conflictos porque después del v1
 * las celdas problemáticas YA están vacías. Pero el backend sigue dando errores.
 *
 * ¿Por qué el backend sigue reportando los mismos errores si el Excel está limpio?
 * → Porque el backend TAMBIÉN detecta como duplicados a los socios que ya existen
 *   en $sociosToInsert (buffer) cuando encuentra el mismo email.
 * → Pero el backend NO agrega el email a $seenEmailsInImport si el socio ya existe
 *   en BD. Y el v3 también saltó existentes.
 *
 * HIPÓTESIS: El usuario vio esos errores de una importación anterior.
 * Hay que verificar que el ACTUAL Excel (ya modificado) realmente pasa la importación.
 *
 * Este script simula COMPLETAMENTE la importación sin hacer cambios en BD,
 * reportando si habría errores o no.
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

$excelPath = __DIR__ . '/../Base_Datos_Socios_Club_Morelia.xlsx';

echo "=== SIMULACIÓN DE IMPORTACIÓN (dry-run) ===\n";
echo "Archivo: " . basename($excelPath) . " (modificado: " . date('Y-m-d H:i:s', filemtime($excelPath)) . ")\n\n";

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

// BD
$usersDbEmails = DB::table('users')
    ->where('rol', 'socio_titular')
    ->pluck('user_id', 'email')
    ->mapWithKeys(fn($uid, $em) => [normalizarEmail($em) => $uid])
    ->toArray();
$sociosDb = DB::table('socios_titulares')
    ->pluck('id_socio', 'numero_accion')
    ->toArray();

// Excel
$spreadsheet  = IOFactory::load($excelPath);
$sheet        = $spreadsheet->getSheet(0);
$rows         = $sheet->toArray(null, true, true, true);
$headerRow    = array_shift($rows); // re-indexa a 0-based

$columnMap = [];
foreach ($headerRow as $colLetter => $val) {
    if (empty($val)) continue;
    $columnMap[normalizeHeader((string)$val)] = $colLetter;
}
$colEmail  = $columnMap['email']         ?? null;
$colAccion = $columnMap['numero_accion'] ?? null;
$colRol    = $columnMap['rol']           ?? null;

echo "Columnas: accion=$colAccion | rol=$colRol | email=$colEmail\n";
echo "Socios BD: " . count($sociosDb) . " | Emails BD: " . count($usersDbEmails) . "\n\n";

// Simulación
$seenEmailsInImport = [];
$errores = [];
$sociosToInsert = [];
$titularesOk = 0;
$titularesSkip = 0;

foreach ($rows as $rowIndex => $row) {
    $filaNum = $rowIndex + 1;

    $rol = mb_strtolower(trim($row[$colRol] ?? ''));
    if (empty($rol)) continue;

    $numAccion = trim($row[$colAccion] ?? '');
    if (empty($numAccion)) continue;

    if ($rol === 'titular') {
        $emailRaw  = trim($row[$colEmail] ?? '');
        $emailNorm = normalizarEmail($emailRaw);

        if (!isset($sociosDb[$numAccion])) {
            // Nuevo socio
            if ($emailNorm !== null) {
                if (isset($usersDbEmails[$emailNorm])) {
                    $errores[] = "Fila $filaNum: email '$emailNorm' ya en BD (user_id={$usersDbEmails[$emailNorm]})";
                } elseif (isset($seenEmailsInImport[$emailNorm])) {
                    $errores[] = "Fila $filaNum: email '$emailNorm' duplicado con fila {$seenEmailsInImport[$emailNorm]}";
                } else {
                    $seenEmailsInImport[$emailNorm] = $filaNum;
                }
            }
            $sociosToInsert[$numAccion] = true;
            $titularesOk++;
        } else {
            $titularesSkip++; // ya existe, update
        }
    } elseif ($rol === 'miembro') {
        if (!isset($sociosDb[$numAccion]) && !isset($sociosToInsert[$numAccion])) {
            $errores[] = "Fila $filaNum: miembro con accion=$numAccion — titular no encontrado";
        }
    }
}

echo "=== RESULTADO DE LA SIMULACIÓN ===\n";
echo "Titulares nuevos: $titularesOk | Titulares existentes (update): $titularesSkip\n";
echo "Errores encontrados: " . count($errores) . "\n\n";

if (empty($errores)) {
    echo "✅ ¡La importación debería funcionar sin errores!\n";
    echo "   Puedes intentarla de nuevo desde el panel admin.\n";
} else {
    echo "❌ Todavía hay " . count($errores) . " errores:\n\n";
    foreach ($errores as $e) {
        echo "  - $e\n";
    }
}
