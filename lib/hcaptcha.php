<?php
/**
 * hCaptcha verification (admin login). Disabled when HCAPTCHA_SECRET_KEY is empty.
 */

require_once dirname(__DIR__) . '/config.php';

function hcaptcha_enabled(): bool {
    return HCAPTCHA_SITE_KEY !== '' && HCAPTCHA_SECRET_KEY !== '';
}

/**
 * @param string $response Value from POST h-captcha-response
 */
function hcaptcha_verify(string $response): bool {
    if (!hcaptcha_enabled()) {
        return true;
    }
    if ($response === '') {
        return false;
    }
    $payload = http_build_query([
        'secret' => HCAPTCHA_SECRET_KEY,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    $url = 'https://hcaptcha.com/siteverify';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 15,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
    }

    if ($raw === false || $raw === '') {
        return false;
    }
    $json = json_decode($raw, true);
    return is_array($json) && !empty($json['success']);
}
