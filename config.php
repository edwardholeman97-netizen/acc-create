<?php
/**
 * Central config for CDS Account – production ready.
 * Prefer environment variables; optional .env file for local dev.
 */

// Load .env from project root when present (local dev; do not deploy .env with secrets in production)
$envFile = __DIR__ . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
            $k = trim($m[1]);
            $v = trim($m[2], " \t\"'");
            putenv($k . '=' . $v);
            $_ENV[$k] = $v;
        }
    }
}

function env($key, $default = null) {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    return $default;
}

// ----- CSE API -----
define('CSE_API_BASE_URL', rtrim(env('CSE_API_BASE_URL', 'https://uat-cseapi.cse.lk'), '/'));
define('CSE_API_USERNAME', env('CSE_API_USERNAME', ''));
define('CSE_API_PASSWORD', env('CSE_API_PASSWORD', ''));

// ----- Environment -----
define('APP_ENV', env('APP_ENV', 'production')); // production | staging | local
define('APP_DEBUG', env('APP_DEBUG', '0') === '1' || env('APP_DEBUG', '0') === 'true');

// ----- CORS (production: set to your frontend origin, e.g. https://cds.yourdomain.com) -----
define('CORS_ALLOW_ORIGIN', env('CORS_ALLOW_ORIGIN', '*'));

// ----- Paths (token cache: outside web root or in a non-served directory) -----
$storageDir = env('CSE_STORAGE_PATH', __DIR__ . '/storage');
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0750, true);
}
define('CSE_STORAGE_PATH', $storageDir);
define('CSE_TOKEN_CACHE_FILE', CSE_STORAGE_PATH . '/.token_cache.json');
define('CSE_TOKEN_VALID_SECONDS', (int) env('CSE_TOKEN_VALID_SECONDS', '840')); // 14 min
define('CSE_LOG_FILE', CSE_STORAGE_PATH . '/api.log');
