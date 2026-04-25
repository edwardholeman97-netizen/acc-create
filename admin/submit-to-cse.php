<?php
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/database/connection.php';
require_once dirname(__DIR__) . '/lib/cse_api.php';
require_once dirname(__DIR__) . '/lib/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php?err=' . urlencode('Invalid submission id'));
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare(
    'SELECT id, submission_uid, status, form_data, image_paths'
    . ' FROM cds_submissions WHERE id = ? LIMIT 1'
);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: dashboard.php?err=' . urlencode('Submission not found'));
    exit;
}
if (!in_array($row['status'], ['pending_review', 'awaiting_edit'], true)) {
    header('Location: dashboard.php?err=' . urlencode('This submission is already submitted to CSE.'));
    exit;
}

$formData = json_decode($row['form_data'] ?: '[]', true) ?: [];
$imagePaths = json_decode($row['image_paths'] ?: '[]', true) ?: [];

$result = cse_pushPendingToApi($formData, $imagePaths);

if (!$result['success']) {
    header('Location: dashboard.php?err=' . urlencode('CSE submission failed: ' . $result['message']));
    exit;
}

$cseAccountId = (string)$result['accountId'];

$upd = $pdo->prepare(
    'UPDATE cds_submissions'
    . ' SET cse_account_id = ?, account_id = ?, status = ?,'
    . '     submitted_to_cse_at = NOW(), admin_note = NULL, updated_at = NOW()'
    . ' WHERE id = ?'
);
$upd->execute([$cseAccountId, $cseAccountId, 'submitted_to_cse', $id]);

try {
    sendAccountCreationEmail($formData, $cseAccountId);
} catch (Throwable $e) {
    cse_liveLog('Admin push: account creation email failed: ' . $e->getMessage(), 'error');
}

header('Location: dashboard.php?msg=' . urlencode('Submitted to CSE successfully. CSE Account ID: ' . $cseAccountId));
exit;
