<?php
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config.php';

$path = $_GET['path'] ?? '';
if (!$path || strpos($path, '..') !== false || strpos($path, '/') === 0) {
    http_response_code(400);
    exit;
}

$fullPath = rtrim(CSE_STORAGE_PATH, '/') . '/' . $path;
if (!is_file($fullPath)) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($fullPath);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
    $mime = 'image/jpeg';
}
header('Content-Type: ' . $mime);
readfile($fullPath);
exit;
