<?php
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/database/connection.php';
require_once dirname(__DIR__) . '/lib/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$note = trim((string)($_POST['note'] ?? ''));

if ($id <= 0) {
    header('Location: dashboard.php?err=' . urlencode('Invalid submission id'));
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare(
    'SELECT id, status, form_data FROM cds_submissions WHERE id = ? LIMIT 1'
);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: dashboard.php?err=' . urlencode('Submission not found'));
    exit;
}
if (!in_array($row['status'], ['pending_review', 'awaiting_edit'], true)) {
    header('Location: dashboard.php?err=' . urlencode('This submission is already submitted to CSE; cannot send an edit link.'));
    exit;
}

$formData = json_decode($row['form_data'] ?: '[]', true) ?: [];

// Generate a fresh raw token; only the SHA-256 hash is stored.
$rawToken = bin2hex(random_bytes(24));
$tokenHash = hash('sha256', $rawToken);
$expiresAt = (new DateTimeImmutable('+3 days'))->format('Y-m-d H:i:s');
$adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

try {
    $pdo->beginTransaction();
    $ins = $pdo->prepare(
        'INSERT INTO submission_edit_tokens'
        . ' (submission_id, token_hash, expires_at, created_by_admin_id)'
        . ' VALUES (?, ?, ?, ?)'
    );
    $ins->execute([$id, $tokenHash, $expiresAt, $adminId]);

    $upd = $pdo->prepare(
        'UPDATE cds_submissions SET status = ?, admin_note = ?, updated_at = NOW() WHERE id = ?'
    );
    $upd->execute(['awaiting_edit', $note !== '' ? $note : null, $id]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: dashboard.php?err=' . urlencode('Could not create edit link: ' . $e->getMessage()));
    exit;
}

$base = app_public_base_url();
if ($base === '') {
    header('Location: dashboard.php?err=' . urlencode('APP_BASE_URL is not configured. Please set it in your .env so client links can be generated.'));
    exit;
}
$editUrl = $base . '/edit-submission.php?token=' . urlencode($rawToken);

$emailSent = false;
try {
    $emailSent = sendClientEditLinkEmail($formData, $editUrl, $note !== '' ? $note : null);
} catch (Throwable $e) {
    // Logged inside the helper; surface a soft warning below.
}

$flash = $emailSent
    ? 'Edit link emailed to the client. Expires in 3 days, or as soon as they re-submit — whichever comes first.'
    : 'Edit link generated, but the email could not be sent. URL: ' . $editUrl;

header('Location: dashboard.php?msg=' . urlencode($flash));
exit;
