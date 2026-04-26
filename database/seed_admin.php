<?php
/**
 * Create or update an admin user.
 *
 *   php database/seed_admin.php [email] [password] [--super]
 *
 * Defaults: admin@example.com / admin123 / role=admin
 *
 * The --super flag (anywhere in the args) creates / promotes the user to
 * `superadmin`. Without it, an existing superadmin is NOT demoted — only the
 * password is updated.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/connection.php';

$args = array_slice($argv, 1);
$isSuper = false;
$positional = [];
foreach ($args as $a) {
    if ($a === '--super' || $a === '-s') {
        $isSuper = true;
    } else {
        $positional[] = $a;
    }
}

$email = $positional[0] ?? 'admin@example.com';
$password = $positional[1] ?? 'admin123';

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
    $role = $isSuper ? 'superadmin' : 'admin';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($isSuper) {
        // Promote on duplicate: update both password and role.
        $sql = 'INSERT INTO admin_users (email, password_hash, role) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $hash, $role]);
    } else {
        // Do not demote an existing superadmin — only refresh the password.
        $sql = 'INSERT INTO admin_users (email, password_hash, role) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $hash, $role]);
    }

    $label = $isSuper ? 'Superadmin' : 'Admin';
    echo "$label user created/updated: $email\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
