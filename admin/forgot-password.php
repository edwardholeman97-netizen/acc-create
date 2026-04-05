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
$sent = isset($_GET['sent']) && $_GET['sent'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = getDb();
            $stmt = $pdo->prepare('SELECT id FROM admin_users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $ttl = defined('ADMIN_PASSWORD_RESET_EXPIRY_SECONDS') ? (int) ADMIN_PASSWORD_RESET_EXPIRY_SECONDS : 3600;

                $pdo->prepare('DELETE FROM admin_password_resets WHERE admin_user_id = ?')->execute([(int)$user['id']]);
                $ins = $pdo->prepare(
                    'INSERT INTO admin_password_resets (admin_user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))'
                );
                $ins->execute([(int)$user['id'], $tokenHash, $ttl]);

                $base = app_public_base_url();
                if ($base !== '') {
                    $resetUrl = $base . '/admin/reset-password.php?' . http_build_query(['token' => $rawToken]);
                    require_once dirname(__DIR__) . '/lib/email.php';
                    try {
                        sendAdminPasswordResetEmail($email, $resetUrl);
                    } catch (Throwable $mailEx) {
                        if (function_exists('email_log')) {
                            email_log('Forgot password: email send failed: ' . $mailEx->getMessage(), 'error');
                        }
                    }
                }
            }
            header('Location: forgot-password.php?sent=1');
            exit;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'admin_password_resets') !== false
                || stripos($msg, 'Base table or view not found') !== false
                || stripos($msg, "doesn't exist") !== false) {
                $error = 'Password reset is not set up yet: the database table admin_password_resets is missing. Run php database/migrate.php or apply the CREATE TABLE from database/schema.sql, then try again.';
            } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                $error = 'Database error: ' . htmlspecialchars($msg);
            } else {
                $error = 'Something went wrong. Please try again later.';
            }
        } catch (Throwable $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                $error = 'Error: ' . htmlspecialchars($e->getMessage());
            } else {
                $error = 'Something went wrong. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot password — Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {background-image: url('https://www.sampathsecurities.lk/wp-content/uploads/2025/12/orange-mountain-gradient.webp'); background-position: center; background-size: cover; background-repeat: no-repeat; min-height: 100vh; padding: 0; align-content: center;}
        body::before {content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: #ececec; z-index: 2; pointer-events: none; opacity: 0.90;}
        form {padding: 16px; border: 1px solid #E7E7E7; border-radius: 16px 16px 32px 32px;}
        .login-form {position: relative; z-index: 2; max-width: 400px; margin: auto; padding: 32px 16px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; flex-wrap: wrap; flex-direction: column; justify-content: center; align-content: center; gap: 16px; }
        .login-form h1 { font-size: 24px; color: #000; text-align: center; }
        .login-form .error { color: #dc3545; font-size: 14px; margin-bottom: 12px; }
        .login-form .success { color: #2e7d32; font-size: 14px; line-height: 1.5; }
        .login-form a.link { color: #dd4200; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-form">
        <div style="display: flex; justify-content: center;">
            <img style="width: 80%;" src="https://www.sampathsecurities.lk/wp-content/uploads/sampath-securities-logo.png" alt="">
        </div>
        <h1>Forgot password</h1>
        <?php if ($sent): ?>
            <p class="success">If an account exists for that email, we have sent instructions to reset your password. Check your inbox.</p>
            <a class="link" href="login.php">Back to login</a>
        <?php else: ?>
            <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <p style="font-size: 14px; color: #5a6c7d; line-height: 1.5;">Enter your admin email address. We will send you a link to reset your password.</p>
            <form method="post">
                <div style="padding: 0px;" class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <button style="margin-top: 16px; width: 100%;" type="submit" class="btn">Send reset link</button>
            </form>
            <a class="link" href="login.php">Back to login</a>
        <?php endif; ?>
    </div>
</body>
</html>
