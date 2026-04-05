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

// Public site URL (no trailing slash) — used for admin password reset links; if empty, derived from request Host
define('APP_BASE_URL', rtrim((string) env('APP_BASE_URL', ''), '/'));

/** Seconds until admin password reset token expires */
define('ADMIN_PASSWORD_RESET_EXPIRY_SECONDS', 3600);

/**
 * Base URL for links emailed to users (e.g. admin password reset).
 */
function app_public_base_url(): string {
    if (APP_BASE_URL !== '') {
        return APP_BASE_URL;
    }
    if (php_sapi_name() !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $scheme = $https ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
    return '';
}

// ----- CORS (production: set to your frontend origin, e.g. https://cds.yourdomain.com) -----
define('CORS_ALLOW_ORIGIN', env('CORS_ALLOW_ORIGIN', '*'));

// ----- Database (MySQL) -----
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'cds_accounts'));
define('DB_USER', env('DB_USER', ''));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

// ----- Paths (token cache: outside web root or in a non-served directory) -----
$storageDir = env('CSE_STORAGE_PATH', __DIR__ . '/storage');
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0750, true);
}
define('CSE_STORAGE_PATH', $storageDir);
define('CSE_TOKEN_CACHE_FILE', CSE_STORAGE_PATH . '/.token_cache.json');
define('CSE_TOKEN_VALID_SECONDS', (int) env('CSE_TOKEN_VALID_SECONDS', '840')); // 14 min
define('CSE_LOG_FILE', CSE_STORAGE_PATH . '/api.log');

// ----- SMTP (account creation & admin update emails) -----
define('SMTP_HOST', env('SMTP_HOST', ''));
define('SMTP_USERNAME', env('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', ''));
define('SMTP_PORT', (int) env('SMTP_PORT', '465'));
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'ssl'));

// Comma-separated list of addresses to notify when an admin successfully updates a user submission (optional)
define('ADMIN_NOTIFY_EMAIL', env('ADMIN_NOTIFY_EMAIL', ''));

// ----- hCaptcha (admin login) -----
// Create keys at https://www.hcaptcha.com/ — add your host (e.g. sampath.companysite.site) under Sites.
define('HCAPTCHA_SITE_KEY', env('HCAPTCHA_SITE_KEY', ''));
define('HCAPTCHA_SECRET_KEY', env('HCAPTCHA_SECRET_KEY', ''));
