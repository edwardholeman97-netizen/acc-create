<?php
/**
 * Public, token-gated viewer for ID images (selfie / NIC front / NIC back / passport).
 *
 * Used by the client edit-link page so the user can see the ID images they
 * previously uploaded. The caller must present:
 *  - token: the same raw edit-link token used by edit-submission.php
 *  - key:   one of selfie | nic_front | nic_back | passport
 *
 * The token is verified against submission_edit_tokens; the requested key
 * must exist inside THIS submission's image_paths JSON. We never accept
 * arbitrary paths from the client.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/connection.php';

const VIEW_ID_IMAGE_ALLOWED_KEYS = ['selfie', 'nic_front', 'nic_back', 'passport'];

function _vid_fail(int $code): void {
    http_response_code($code);
    exit;
}

$rawToken = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$key      = isset($_GET['key'])   ? trim((string)$_GET['key'])   : '';
if ($rawToken === '' || strlen($rawToken) < 16) _vid_fail(400);
if (!in_array($key, VIEW_ID_IMAGE_ALLOWED_KEYS, true)) _vid_fail(400);

try {
    $pdo = getDb();
    $stmt = $pdo->prepare(
        'SELECT s.image_paths, s.status, t.expires_at'
        . ' FROM submission_edit_tokens t'
        . ' INNER JOIN cds_submissions s ON s.id = t.submission_id'
        . ' WHERE t.token_hash = ? LIMIT 1'
    );
    $stmt->execute([hash('sha256', $rawToken)]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    _vid_fail(500);
}
if (!$row) _vid_fail(403);
if (strtotime($row['expires_at']) < time()) _vid_fail(410);
if (!in_array($row['status'], ['pending_review', 'awaiting_edit'], true)) _vid_fail(410);

$imagePaths = json_decode($row['image_paths'] ?: '[]', true) ?: [];
$relPath = isset($imagePaths[$key]) ? (string)$imagePaths[$key] : '';
if ($relPath === '' || strpos($relPath, '..') !== false || strpos($relPath, '/') === 0) _vid_fail(404);

$absPath = rtrim(CSE_STORAGE_PATH, '/') . '/' . ltrim($relPath, '/');
$realBase = realpath(rtrim(CSE_STORAGE_PATH, '/'));
$realFull = realpath($absPath);
if (!$realFull || !$realBase || strpos($realFull, $realBase . DIRECTORY_SEPARATOR) !== 0) _vid_fail(403);
if (!is_file($realFull)) _vid_fail(404);

$mime = function_exists('mime_content_type') ? @mime_content_type($realFull) : null;
$allowed = ['image/jpeg', 'image/png'];
if (!$mime || !in_array(strtolower($mime), $allowed, true)) {
    $extMime = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    ];
    $ext = strtolower(pathinfo($realFull, PATHINFO_EXTENSION));
    if (!isset($extMime[$ext])) _vid_fail(415);
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
