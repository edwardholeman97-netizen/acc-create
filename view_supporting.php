<?php
/**
 * Public, token-gated viewer for supporting documents.
 *
 * Used by the client edit-link page so the user can re-open documents they
 * previously uploaded. The caller must present:
 *  - token: the same raw edit-link token used by edit-submission.php
 *  - fid:   the file id stored inside cds_submissions.supporting_documents
 *
 * The token is verified against submission_edit_tokens; the requested file
 * must exist inside THIS submission's supporting_documents JSON. We never
 * accept arbitrary paths from the client.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/lib/supporting_docs.php';

function _vs_fail(int $code): void {
    http_response_code($code);
    exit;
}

$rawToken = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$fid      = isset($_GET['fid'])   ? trim((string)$_GET['fid'])   : '';
if ($rawToken === '' || strlen($rawToken) < 16) _vs_fail(400);
if ($fid === '' || !preg_match('/^[a-zA-Z0-9_-]{4,64}$/', $fid)) _vs_fail(400);

try {
    $pdo = getDb();
    $stmt = $pdo->prepare(
        'SELECT s.supporting_documents, s.status, t.expires_at'
        . ' FROM submission_edit_tokens t'
        . ' INNER JOIN cds_submissions s ON s.id = t.submission_id'
        . ' WHERE t.token_hash = ? LIMIT 1'
    );
    $stmt->execute([hash('sha256', $rawToken)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    _vs_fail(500);
}
if (!$row) _vs_fail(403);
if (strtotime($row['expires_at']) < time()) _vs_fail(410);
if (!in_array($row['status'], ['pending_review', 'awaiting_edit'], true)) _vs_fail(410);

$docs = supporting_docs_normalize($row['supporting_documents'] ?? null);
$match = null;
foreach ($docs['categories'] as $cat) {
    foreach ($cat['files'] as $f) {
        if (($f['id'] ?? '') === $fid) { $match = $f; break 2; }
    }
}
if (!$match) _vs_fail(404);

$relPath = (string)($match['path'] ?? '');
if ($relPath === '' || strpos($relPath, '..') !== false || strpos($relPath, '/') === 0) _vs_fail(400);

$absPath = rtrim(CSE_STORAGE_PATH, '/') . '/' . ltrim($relPath, '/');
$realBase = realpath(rtrim(CSE_STORAGE_PATH, '/'));
$realFull = realpath($absPath);
if (!$realFull || !$realBase || strpos($realFull, $realBase . DIRECTORY_SEPARATOR) !== 0) _vs_fail(403);
if (!is_file($realFull)) _vs_fail(404);

$mime = function_exists('mime_content_type') ? @mime_content_type($realFull) : null;
$allowed = get_supporting_doc_allowed_mimes();
if (!$mime || !in_array(strtolower($mime), $allowed, true)) {
    $extMime = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
    ];
    $ext = strtolower(pathinfo($realFull, PATHINFO_EXTENSION));
    if (!isset($extMime[$ext])) _vs_fail(415);
    $mime = $extMime[$ext];
}

$filename = basename($realFull);
$safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($realFull));
header('Content-Disposition: inline; filename="' . $safeFilename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
readfile($realFull);
exit;
