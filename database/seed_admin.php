<?php
/**
 * Create admin user. Run: php database/seed_admin.php [email] [password]
 * Default: admin@example.com / admin123
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/connection.php';

$email = $argv[1] ?? 'admin@example.com';
$password = $argv[2] ?? 'admin123';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email.\n");
    exit(1);
}

if (strlen($password) < 6) {
    fwrite(STDERR, "Password must be at least 6 characters.\n");
    exit(1);
}

try {
    $pdo = getDb();
    $stmt = $pdo->prepare('INSERT INTO admin_users (email, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)');
    $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
    echo "Admin user created/updated: $email\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
