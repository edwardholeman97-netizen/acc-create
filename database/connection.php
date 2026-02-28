<?php
/**
 * PDO MySQL connection singleton for CDS app.
 */
require_once dirname(__DIR__) . '/config.php';

$pdo = null;

function getDb(): PDO {
    global $pdo;
    if ($pdo !== null) {
        return $pdo;
    }
    $host = env('DB_HOST', 'localhost');
    $name = env('DB_NAME', 'cds_accounts');
    $user = env('DB_USER', '');
    $pass = env('DB_PASSWORD', '');
    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}
