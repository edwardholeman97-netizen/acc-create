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
        body {background-image: url('https://www.sampathsecurities.lk/wp-content/uploads/2025/12/orange-mountain-gradient.webp'); background-position: center; background-size: cover; background-repeat: no-repeat; min-height: 100vh; padding: 0; align-content: center;}
        body::before {content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: #ececec; z-index: 2; pointer-events: none; opacity: 0.90;}
        form {padding: 16px; border: 1px solid #E7E7E7; border-radius: 16px 16px 32px 32px;}
        .login-form {position: relative; z-index: 2; max-width: 400px; margin: auto; padding: 32px 16px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; flex-wrap: wrap; flex-direction: column; justify-content: center; align-content: center; gap: 16px; }
        .login-form h1 { font-size: 24px; color: #000; text-align: center; }
        .login-form .error { color: #dc3545; font-size: 14px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="login-form">
        <div style="display: flex; justify-content: center;">
            <img style="width: 80%;" src="https://www.sampathsecurities.lk/wp-content/uploads/sampath-securities-logo.png">
        </div>
        <h1>CDS Admin Login</h1>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div style="padding: 0px;" class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div style="padding: 0px;" class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button style="margin-top: 16px; width: 100%;" type="submit" class="btn">Log in</button>
        </form>
    </div>
</body>
</html>
