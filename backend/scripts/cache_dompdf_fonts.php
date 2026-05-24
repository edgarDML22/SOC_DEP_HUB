<?php
/**
 * Script de pre-calentamiento de fuentes para DOMPDF.
 * Se ejecuta durante el build de Docker para generar los archivos .ufm
 * en storage/fonts, evitando errores de escritura en tiempo de ejecución.
 */

require '/var/www/vendor/autoload.php';

$options = new \Dompdf\Options();
$options->setChroot('/var/www');
$options->setFontDir('/var/www/storage/fonts');
$options->setFontCache('/var/www/storage/fonts');

$dompdf = new \Dompdf\Dompdf($options);
$fontMetrics = new \Dompdf\FontMetrics($dompdf->getCanvas(), $options);

$fonts = [
    ['family' => 'Inter',         'weight' => 'normal', 'style' => 'normal', 'path' => '/var/www/resources/fonts/Inter-Regular.ttf'],
    ['family' => 'Inter',         'weight' => 'bold',   'style' => 'normal', 'path' => '/var/www/resources/fonts/Inter-Bold.ttf'],
    ['family' => 'FontAwesome',   'weight' => '900',    'style' => 'normal', 'path' => '/var/www/resources/fonts/fa-solid-900.ttf'],
];

foreach ($fonts as $font) {
    if (file_exists($font['path'])) {
        $fontMetrics->registerFont(
            ['family' => $font['family'], 'weight' => $font['weight'], 'style' => $font['style']],
            $font['path']
        );
        echo "[OK] Fuente cacheada: {$font['family']} ({$font['weight']})\n";
    } else {
        echo "[WARN] No se encontro: {$font['path']}\n";
    }
}

echo "[OK] Cache de fuentes DOMPDF completado.\n";
