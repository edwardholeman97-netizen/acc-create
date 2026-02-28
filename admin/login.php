<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter email and password.';
    } else {
        try {
            $pdo = getDb();
            $stmt = $pdo->prepare('SELECT id, email, password_hash FROM admin_users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_email'] = $user['email'];
                header('Location: dashboard.php');
                exit;
            }
            $error = 'Invalid email or password.';
        } catch (Throwable $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .login-form { max-width: 400px; margin: 80px auto; padding: 30px; background: #f9fafb; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .login-form h1 { margin-bottom: 24px; font-size: 24px; color: #1a237e; }
        .login-form .form-group { margin-bottom: 16px; }
        .login-form label { display: block; margin-bottom: 6px; font-weight: 600; color: #34495e; }
        .login-form input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; }
        .login-form .error { color: #dc3545; font-size: 14px; margin-bottom: 12px; }
        .login-form .btn { width: 100%; padding: 12px; background: #1a237e; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .login-form .btn:hover { background: #283593; }
    </style>
</head>
<body>
    <div class="login-form">
        <h1>Admin Login</h1>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Log in</button>
        </form>
    </div>
</body>
</html>
