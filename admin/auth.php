<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_email'])) {
    header('Location: login.php');
    exit;
}

if (!function_exists('admin_is_super')) {
    function admin_is_super(): bool
    {
        return ($_SESSION['admin_role'] ?? 'admin') === 'superadmin';
    }
}
