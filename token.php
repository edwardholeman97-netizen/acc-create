<?php
require_once __DIR__ . '/config.php';

// ==================== AUTH FUNCTION ====================
function getAuthToken() {
    $url = CSE_API_BASE_URL . '/token';
    $data = [
        'username' => CSE_API_USERNAME,
        'password' => CSE_API_PASSWORD,
        'grant_type' => 'password'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $result) {
        $response = json_decode($result, true);
        if (isset($response['access_token'])) {
            return $response['access_token'];
        }
    }

    return null;
}

// ==================== RUN AUTH AND ECHO TOKEN ====================
$token = getAuthToken();

if ($token) {
    echo "Bearer token:\n$token\n";
} else {
    echo "Failed to retrieve token.\n";
}
