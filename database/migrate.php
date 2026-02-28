<?php
/**
 * Run database migration. Creates database if needed, then runs schema.sql
 * Usage: php database/migrate.php
 */
require_once dirname(__DIR__) . '/config.php';

$host = env('DB_HOST', 'localhost');
$name = env('DB_NAME', 'cds_accounts');
$user = env('DB_USER', '');
$pass = env('DB_PASSWORD', '');

echo "Running migration for database: $name\n";

try {
    // Connect without database to create it
    $dsn = "mysql:host={$host};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}`");
    echo "Database '$name' ready.\n";

    $pdo->exec("USE `{$name}`");

    $schemaFile = __DIR__ . '/schema.sql';
    if (!is_file($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }

    $sql = file_get_contents($schemaFile);
    // Remove comments and empty lines, split by semicolon
    $statements = array_filter(
        array_map('trim', explode(';', preg_replace('/--.*$/m', '', $sql))),
        fn($s) => $s !== ''
    );

    foreach ($statements as $stmt) {
        $pdo->exec($stmt);
        if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $stmt, $m)) {
            echo "  Table `{$m[1]}` ready.\n";
        } else {
            echo "  Statement executed.\n";
        }
    }

    echo "Migration completed successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
