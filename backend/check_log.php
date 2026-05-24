<?php
$logPath = __DIR__ . '/storage/logs/laravel.log';
if (!file_exists($logPath)) {
    die("No log found");
}
$lines = file($logPath);
$lines = array_reverse($lines);
$errorLines = [];
foreach ($lines as $line) {
    if (strpos($line, 'local.ERROR:') !== false) {
        $errorLines[] = $line;
        if (count($errorLines) >= 3) break;
    }
}
print_r($errorLines);
