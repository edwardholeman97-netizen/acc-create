<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/form_constants.php';
require_once __DIR__ . '/lib/supporting_docs.php';

/**
 * Public client edit page. Validates a 3-day token, then renders the same
 * multi-step form prefilled with the client's previously-submitted data.
 * On submit, the JS posts to api.php?step=client-resubmit which pushes directly
 * to CSE and locks the row.
 *
 * The link is reusable until expiry or until the row flips to submitted_to_cse
 * (whichever comes first). Once submitted, the status check rejects further use.
 */

function render_invalid_link_page(string $reason): void {
    http_response_code(410);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Edit link unavailable</title>
        <link rel="stylesheet" href="assets/css/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body { font-family: 'DM Sans', system-ui, sans-serif; background: #f5f6f8; }
            .invalid-link-card { max-width: 520px; margin: 80px auto; background: #fff; border-radius: 16px; padding: 36px 32px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); text-align: center; }
            .invalid-link-card .icon { font-size: 48px; color: #d97706; margin-bottom: 12px; }
            .invalid-link-card h1 { margin: 0 0 8px; font-size: 22px; color: #1f2937; }
            .invalid-link-card p { color: #4b5563; margin: 8px 0 16px; line-height: 1.5; }
            .invalid-link-card small { color: #9ca3af; }
        </style>
    </head>
    <body>
        <div class="invalid-link-card">
            <div class="icon"><i class="fas fa-circle-exclamation"></i></div>
            <h1>This edit link can no longer be used</h1>
            <p><?= htmlspecialchars($reason) ?></p>
            <p>Please contact our team if you still need to update your application.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$rawToken = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
if ($rawToken === '' || strlen($rawToken) < 16) {
    render_invalid_link_page('The link is missing or malformed.');
}

$pdo = getDb();
$tokenHash = hash('sha256', $rawToken);
$stmt = $pdo->prepare(
    'SELECT t.id, t.expires_at,'
    . ' s.id AS submission_id, s.submission_uid, s.form_data, s.image_paths,'
    . ' s.supporting_documents, s.status, s.admin_note'
    . ' FROM submission_edit_tokens t'
    . ' INNER JOIN cds_submissions s ON s.id = t.submission_id'
    . ' WHERE t.token_hash = ? LIMIT 1'
);
$stmt->execute([$tokenHash]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    render_invalid_link_page('This link is not recognised. It may have been replaced by a newer one.');
}
if (strtotime($row['expires_at']) < time()) {
    render_invalid_link_page('This link has expired.');
}
if (!in_array($row['status'], ['pending_review', 'awaiting_edit'], true)) {
    render_invalid_link_page('Your application has already been submitted to CSE — no further changes are needed.');
}

$prefill = json_decode($row['form_data'] ?: '[]', true) ?: [];
$adminNote = trim((string)($row['admin_note'] ?? ''));
$supportingDocsForClient = supporting_docs_normalize($row['supporting_documents'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update your CDS Application</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baskervville:ital,wght@0,400..700;1,400..700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    <style>
        .admin-note-banner {
            max-width: 1200px;
            margin: 14px auto 0;
            background: #FFF8E6;
            border-left: 5px solid #FF8800;
            padding: 16px 22px;
            border-radius: 8px;
            color: #3D3D3D;
        }
        .admin-note-banner strong { display: block; margin-bottom: 6px; color: #B45309; }
        .admin-note-banner p { margin: 0; white-space: pre-wrap; line-height: 1.5; }
        .edit-link-meta {
            max-width: 1200px;
            margin: 8px auto 0;
            font-size: 12px;
            color: #6b7280;
            text-align: right;
            padding: 0 22px;
        }
    </style>
</head>

<body>
<div class="container">
    <?php if ($adminNote !== ''): ?>
        <div class="admin-note-banner" role="note">
            <strong><i class="fas fa-circle-info"></i> Note from our review team</strong>
            <p><?= nl2br(htmlspecialchars($adminNote)) ?></p>
        </div>
    <?php endif; ?>
    <div class="edit-link-meta">
        Edit link expires <?= htmlspecialchars(date('M j, Y', strtotime($row['expires_at']))) ?>
    </div>
<?php include __DIR__ . '/includes/form_steps.php'; ?>
</div>

<script>
// Mode: "resubmit" - client re-submits via secure edit link. Pushes directly to CSE.
window.__formConfig = {
    mode: 'resubmit',
    formData: <?= json_encode($prefill, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    lockedKeys: <?= json_encode(get_form_locked_field_keys()) ?>,
    token: <?= json_encode($rawToken) ?>,
    supportingDocs: <?= json_encode($supportingDocsForClient, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};
</script>
<?php include __DIR__ . '/includes/form_scripts.php'; ?>
</body>

</html>
