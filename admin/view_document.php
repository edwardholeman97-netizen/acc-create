<?php
/**
 * Inline serve a supporting document (PDF or image) from storage/uploads/.
 *
 * Same path-traversal guard as view_image.php, but extends the MIME whitelist
 * to include application/pdf for the supporting-documents feature.
 */
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config.php';

$path = $_GET['path'] ?? '';
if (!$path || !is_string($path) || strpos($path, '..') !== false || strpos($path, '/') === 0) {
    http_response_code(400);
    exit;
}

$fullPath = rtrim(CSE_STORAGE_PATH, '/') . '/' . $path;
$realBase = realpath(rtrim(CSE_STORAGE_PATH, '/'));
$realFull = realpath($fullPath);
if (!$realFull || !$realBase || strpos($realFull, $realBase . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(403);
    exit;
}
if (!is_file($realFull)) {
    http_response_code(404);
    exit;
}

$mime = function_exists('mime_content_type') ? @mime_content_type($realFull) : null;
$allowed = [
    'application/pdf',
    'image/jpeg',
    'image/jpg',
    'image/pjpeg',
    'image/png',
    'image/x-png',
    'image/gif',
    'image/webp',
];
if (!$mime || !in_array(strtolower($mime), $allowed, true)) {
    // Fall back to extension sniffing — but still refuse anything not on the list.
    $ext = strtolower(pathinfo($realFull, PATHINFO_EXTENSION));
    $extMime = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
    ];
    if (!isset($extMime[$ext])) {
        http_response_code(415);
        exit;
    }
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
