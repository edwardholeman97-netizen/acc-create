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

const ADMIN_RESET_PASSWORD_MIN_LEN = 8;
const ADMIN_PW_RESET_SESSION_KEY = 'admin_pw_reset';

/**
 * Load a non-expired reset row using session (set after a valid email link visit).
 */
function admin_reset_row_from_session(PDO $pdo): ?array {
    if (empty($_SESSION[ADMIN_PW_RESET_SESSION_KEY]) || !is_array($_SESSION[ADMIN_PW_RESET_SESSION_KEY])) {
        return null;
    }
    $rid = (int) ($_SESSION[ADMIN_PW_RESET_SESSION_KEY]['rid'] ?? 0);
    $uid = (int) ($_SESSION[ADMIN_PW_RESET_SESSION_KEY]['uid'] ?? 0);
    if ($rid < 1 || $uid < 1) {
        return null;
    }
    $stmt = $pdo->prepare(
        'SELECT r.id AS reset_id, r.admin_user_id, u.email
         FROM admin_password_resets r
         INNER JOIN admin_users u ON u.id = r.admin_user_id
         WHERE r.id = ? AND r.admin_user_id = ? AND r.expires_at > NOW()'
    );
    $stmt->execute([$rid, $uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

$error = '';
$validRow = null;
$urlToken = trim((string) ($_GET['token'] ?? ''));

try {
    $pdo = getDb();

    if ($urlToken !== '') {
        $urlToken = strtolower(preg_replace('/[^0-9a-f]/i', '', $urlToken));
        if (strlen($urlToken) !== 64) {
            unset($_SESSION[ADMIN_PW_RESET_SESSION_KEY]);
            $error = 'This reset link is invalid or has expired. Please request a new one.';
        } else {
            $tokenHash = hash('sha256', $urlToken);
            $stmt = $pdo->prepare(
                'SELECT r.id AS reset_id, r.admin_user_id, u.email
                 FROM admin_password_resets r
                 INNER JOIN admin_users u ON u.id = r.admin_user_id
                 WHERE r.token_hash = ? AND r.expires_at > NOW()'
            );
            $stmt->execute([$tokenHash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                $_SESSION[ADMIN_PW_RESET_SESSION_KEY] = [
                    'rid' => (int) $row['reset_id'],
                    'uid' => (int) $row['admin_user_id'],
                ];
                header('Location: reset-password.php');
                exit;
            }
            unset($_SESSION[ADMIN_PW_RESET_SESSION_KEY]);
            $error = 'This reset link is invalid or has expired. Please request a new one.';
        }
    } else {
        $validRow = admin_reset_row_from_session($pdo);
    }
} catch (Throwable $e) {
    $error = 'Unable to verify reset link. Ensure the database migration has been run (admin_password_resets table).';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDb();
        if (!$validRow) {
            $validRow = admin_reset_row_from_session($pdo);
        }
    } catch (Throwable $e) {
        $validRow = null;
    }

    if (!$validRow) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    } else {
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password_confirm'] ?? '';
        if (strlen($password) < ADMIN_RESET_PASSWORD_MIN_LEN) {
            $error = 'Password must be at least ' . ADMIN_RESET_PASSWORD_MIN_LEN . ' characters.';
        } elseif ($password !== $password2) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $pdo = getDb();
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
                    ->execute([$newHash, (int) $validRow['admin_user_id']]);
                $pdo->prepare('DELETE FROM admin_password_resets WHERE admin_user_id = ?')
                    ->execute([(int) $validRow['admin_user_id']]);
                unset($_SESSION[ADMIN_PW_RESET_SESSION_KEY]);
                header('Location: login.php?reset=1');
                exit;
            } catch (Throwable $e) {
                $error = 'Could not update password. Please try again.';
            }
        }
    }
} elseif ($urlToken === '' && $validRow === null && $error === '') {
    $error = 'Missing reset token. Open the link from your email, or request a new reset.';
}

$showForm = $validRow && ($error === '' || ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password — Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {background-image: url('https://www.sampathsecurities.lk/wp-content/uploads/2025/12/orange-mountain-gradient.webp'); background-position: center; background-size: cover; background-repeat: no-repeat; min-height: 100vh; padding: 0; align-content: center;}
        body::before {content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: #ececec; z-index: 2; pointer-events: none; opacity: 0.90;}
        form {padding: 16px; border: 1px solid #E7E7E7; border-radius: 16px 16px 32px 32px;}
        .login-form {position: relative; z-index: 2; max-width: 400px; margin: auto; padding: 32px 16px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex; flex-wrap: wrap; flex-direction: column; justify-content: center; align-content: center; gap: 16px; }
        .login-form h1 { font-size: 24px; color: #000; text-align: center; }
        .login-form .error { color: #dc3545; font-size: 14px; margin-bottom: 12px; }
        .login-form a.link { color: #dd4200; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-form">
        <div style="display: flex; justify-content: center;">
            <img style="width: 80%;" src="https://www.sampathsecurities.lk/wp-content/uploads/sampath-securities-logo.png" alt="">
        </div>
        <h1>Set new password</h1>
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($showForm): ?>
            <p style="font-size: 14px; color: #5a6c7d;">Account: <strong><?= htmlspecialchars($validRow['email']) ?></strong></p>
            <form method="post" action="reset-password.php">
                <div style="padding: 0px;" class="form-group">
                    <label for="password">New password</label>
                    <input type="password" id="password" name="password" required minlength="<?= (int)ADMIN_RESET_PASSWORD_MIN_LEN ?>" autocomplete="new-password">
                </div>
                <div style="padding: 0px;" class="form-group">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="<?= (int)ADMIN_RESET_PASSWORD_MIN_LEN ?>" autocomplete="new-password">
                </div>
                <button style="margin-top: 16px; width: 100%;" type="submit" class="btn">Update password</button>
            </form>
        <?php else: ?>
            <a class="link" href="forgot-password.php">Request a new link</a>
            <a class="link" href="login.php">Back to login</a>
        <?php endif; ?>
    </div>
</body>
</html>
