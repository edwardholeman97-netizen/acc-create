<?php
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$ids = [];
if (!empty($_POST['ids'])) {
    $ids = array_map('intval', (array)$_POST['ids']);
}

if (empty($ids)) {
    header('Location: dashboard.php?msg=' . urlencode('No records selected.'));
    exit;
}

try {
    $pdo = getDb();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, account_id FROM cds_submissions WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $delStmt = $pdo->prepare("DELETE FROM cds_submissions WHERE id IN ($placeholders)");
    $delStmt->execute($ids);

    $baseDir = rtrim(CSE_STORAGE_PATH, '/') . '/uploads';
    foreach ($rows as $r) {
        $dir = $baseDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $r['account_id']);
        if (is_dir($dir)) {
            array_map('unlink', glob("$dir/*"));
            rmdir($dir);
        }
    }

    header('Location: dashboard.php?msg=' . urlencode('Record(s) deleted.'));
} catch (Throwable $e) {
    header('Location: dashboard.php?msg=' . urlencode('Delete failed: ' . $e->getMessage()));
}
exit;
