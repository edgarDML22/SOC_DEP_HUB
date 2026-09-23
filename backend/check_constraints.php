<?php
try {
    $pdo = new PDO('pgsql:host=ep-cold-surf-aihr7hz4.c-4.us-east-1.aws.neon.tech;dbname=centro_deportivo;sslmode=require;options=\'endpoint=ep-cold-surf-aihr7hz4\'', 'laravel_user', 'S0C-D3P-HU806');
    $stmt = $pdo->query("SELECT conname, pg_get_constraintdef(con.oid) FROM pg_constraint con JOIN pg_class rel ON rel.oid = con.conrelid WHERE rel.relname = 'socios_titulares'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
