<?php
// resource.php - CSE API Resource Endpoints (dropdowns / reference data)
// Full API doc (fields, types): https://docs.google.com/spreadsheets/d/1RmWTSGOAT9E408jGtw7_Gm6FQeGGI_njWcJ2uqcE070/edit?usp=sharing
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (CORS_ALLOW_ORIGIN ?: '*'));
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$response = ['success' => false, 'message' => '', 'data' => []];

// ==================== AUTH (token cached per config) ====================
function getAuthToken() {
    $now = time();
    $cacheFile = CSE_TOKEN_CACHE_FILE;
    if (file_exists($cacheFile) && is_readable($cacheFile)) {
        $cached = @json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached['token']) && !empty($cached['expires']) && $cached['expires'] > $now) {
            return $cached['token'];
        }
    }

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
        $parsed = json_decode($result, true);
        if (isset($parsed['access_token'])) {
            $token = $parsed['access_token'];
            $dir = dirname($cacheFile);
            if (!is_dir($dir)) @mkdir($dir, 0750, true);
            @file_put_contents($cacheFile, json_encode([
                'token' => $token,
                'expires' => $now + CSE_TOKEN_VALID_SECONDS
            ]), LOCK_EX);
            @chmod($cacheFile, 0600);
            return $token;
        }
    }
    return null;
}




// ==================== CURL WRAPPER ====================
function callCSEAPI($endpoint, $method = 'POST', $data = null, $requiresAuth = true) {

    $url = CSE_API_BASE_URL . $endpoint;
    $token = $requiresAuth ? getAuthToken() : null;

    $ch = curl_init();

    $headers = [
        'Accept: application/json'
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ];

    // Set method and data
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        if ($data) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }
    } elseif ($method === 'GET' && $data) {
        $options[CURLOPT_URL] .= '?' . http_build_query($data);
    }

    curl_setopt_array($ch, $options);

    // === LOG REQUEST ===
    writeApiLog(
        "================ REQUEST ================\n" .
        "Time: " . date('Y-m-d H:i:s') . "\n" .
        "URL: {$url}\n" .
        "Method: {$method}\n" .
        "Payload: " . json_encode($data, JSON_PRETTY_PRINT) . "\n"
    );

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // === LOG RESPONSE ===
    writeApiLog(
        "================ RESPONSE ================\n" .
        "HTTP Code: {$httpCode}\n" .
        "Curl Error: {$curlError}\n" .
        "Raw Response: {$result}\n\n"
    );

    return [
        'success' => ($httpCode === 200),
        'http_code' => $httpCode,
        'our_sent_data' => $data,
        'data' => $result ? json_decode($result, true) : null,
        'raw' => $result
    ];
}








// ==================== MAIN REQUEST HANDLER ====================
try {
    // Get requested action
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('No action specified');
    }
    

    // Route to appropriate function
    switch ($action) {
        case 'getCountries':
            $result = getCountries();
            break;

        case 'getDistricts':
            $result = getDistricts();
            break;

        case 'getBanks':
            $result = getBanks();
            break;
            
        case 'getBranches':
            $bankCode = $_GET['BANK_CODE'] ?? $_POST['BANK_CODE'] ?? '';
            if (empty($bankCode)) {
                throw new Exception('Bank code is required');
            }
            $result = getBankBranches($bankCode);
            break;
            
        case 'getBrokers':
            $result = getBrokers();
            // Restrict to S C SECURITIES only (or ALLOWED_BROKER_FILTER from .env)
            $filter = env('ALLOWED_BROKER_FILTER', 'S C SECURITIES');
            if ($filter !== '' && !empty($result['data'])) {
                $raw = $result['data'];
                $items = (is_array($raw) && isset($raw[0]) && is_array($raw[0])) ? $raw : ($raw['Data'] ?? $raw['data'] ?? []);
                if (is_array($items)) {
                    $filterNorm = preg_replace('/\s+/', ' ', trim($filter));
                    $filtered = array_values(array_filter($items, function ($b) use ($filterNorm) {
                        $name = $b['BROKER_FULL_NAME'] ?? $b['BrokerFullName'] ?? '';
                        $nameNorm = preg_replace('/\s+/', ' ', trim((string)$name));
                        return stripos($nameNorm, $filterNorm) !== false;
                    }));
                    $result['data'] = $filtered;
                }
            }
            break;

        case 'getTitles':
            $result = getTitles();
            break;

        case 'getInvestAdvisors':
            $result = getInvestAdvisors();
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    


    $response['success'] = $result['success'] ?? false;
    $rawData = $result['data'] ?? null;
    // CSE may return a top-level array [ {...}, {...} ] or an object { "Data": [...] }; ensure we always send an array
    if (is_array($rawData) && isset($rawData[0]) && is_array($rawData[0])) {
        $response['data'] = $rawData;
    } elseif (is_array($rawData) && (isset($rawData['Data']) || isset($rawData['data']))) {
        $response['data'] = $rawData['Data'] ?? $rawData['data'] ?? [];
    } else {
        $response['data'] = is_array($rawData) ? $rawData : [];
    }
    $response['http_code'] = $result['http_code'] ?? 0;
    // When request failed (e.g. 401), surface API message so user knows to check credentials
    if (!$response['success'] && is_array($rawData)) {
        $response['message'] = $rawData['Message'] ?? $rawData['message'] ?? $rawData['error_description'] ?? $rawData['error'] ?? '';
    }
    if (!$response['success'] && $response['message'] === '' && empty(CSE_API_USERNAME)) {
        $response['message'] = 'API credentials not set. Add CSE_API_USERNAME and CSE_API_PASSWORD to .env';
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

// Return JSON response
echo json_encode($response);
exit;


















// ==================== INDIVIDUAL RESOURCE FUNCTIONS ====================

/**
 * Get banks list
 * Endpoint: POST /api/OtherServices/GetBank
 */
function getBanks() {
    return callCSEAPI('/api/OtherServices/GetBank', 'POST', null, true);
}

/**
 * Get bank branches for specific bank
 * Endpoint: POST /api/OtherServices/GetBankBranch?bankCode={code}
 */
function getBankBranches($bankCode) {
    return callCSEAPI('/api/OtherServices/GetBankBranch?bankCode='.$bankCode, 'POST', ['bankCode' => $bankCode], true);
}


/**
 * Gets countries list from CSE API
 */
function getCountriesFromCSE($token) {
    return callCSEAPI('/api/OtherServices/GetCountry', 'POST', null, true);
    
    return null;
}



/**
 * Gets countries list from CSE API
 * 
 * Endpoint: POST /api/OtherServices/GetCountry
 * Method: POST
 * Authentication: Required (Bearer token)
 * 
 * Response format:
 * [
 *     {
 *         "COUNTRY_CODE": "DH",
 *         "COUNTRY_NAME": "Abu Dhabi"
 *     },
 *     {
 *         "COUNTRY_CODE": "AF",
 *         "COUNTRY_NAME": "Afghanistan"
 *     },
 *     {
 *         "COUNTRY_CODE": "LK",
 *         "COUNTRY_NAME": "Sri Lanka"
 *     }
 * ]
 * 
 * @return array API response with success status and countries data
 */
function getCountries() {
    return callCSEAPI('/api/OtherServices/GetCountry', 'POST', null, true);
}


/**
 * Gets districts list from CSE API (Sri Lanka districts)
 * 
 * Endpoint: POST /api/OtherServices/GetDistrict
 * Method: POST
 * Authentication: Required (Bearer token)
 * 
 * Response format:
 * [
 *     {
 *         "DISTRICT_CODE": 1,
 *         "DISTRICT_NAME": "COLOMBO"
 *     },
 *     {
 *         "DISTRICT_CODE": 2,
 *         "DISTRICT_NAME": "KALUTARA"
 *     },
 *     {
 *         "DISTRICT_CODE": 3,
 *         "DISTRICT_NAME": "GAMPAHA"
 *     }
 * ]
 * 
 * @return array API response with success status and districts data
 */
function getDistricts() {
    return callCSEAPI('/api/OtherServices/GetDistrict', 'POST', null, true);
}



/**
 * Gets brokers list from CSE API
 * 
 * Endpoint: POST /api/OtherServices/GetBroker
 * Method: POST
 * Authentication: Required (Bearer token)
 * 
 * Response format:
 * [
 *     {
 *         "BROKER_ID": "NWS",
 *         "BROKER_FULL_NAME": "ACAP STOCK BROKERS (PVT) LTD"
 *     },
 *     {
 *         "BROKER_ID": "FWS",
 *         "BROKER_FULL_NAME": "ACUITY STOCKBROKERS (PVT) LTD"
 *     },
 *     {
 *         "BROKER_ID": "STS",
 *         "BROKER_FULL_NAME": "ALMAS EQUITIES (PRIVATE) LIMITED"
 *     }
 * ]
 * 
 * Note: BROKER_ID is 3 characters max, suitable for SaveUser API
 * 
 * @return array API response with success status and brokers data
 */
function getBrokers() {
    return callCSEAPI('/api/OtherServices/GetBroker', 'POST', null, true);
}


/**
 * Gets title for the person 
 * 
 * Endpoint: POST /api/OtherServices/GetTitle
 * Method: POST
 * Authentication: Required (Bearer token)
 *
 * 
 * Note: Titles are like Mr, Mrs, Miss, Dr, Prof etc.
 * Frontend uses valueField TITLE_CODE, displayField TITLE_NAME; if CSE returns different keys, update index.php loadResource call.
 * 
 * @return array API response with success status and title data
 */
function getTitles() {
    return callCSEAPI('/api/OtherServices/GetTitle', 'POST', null, true);
}

/**
 * Get investment advisors (for INVESTOR_ID dropdown; doc: INVESTOR_ID NUMBER(5)).
 * Endpoint: POST /api/OtherServices/GetInvestAdvisors
 * Frontend uses valueField INVESTOR_ID, displayField INVESTOR_NAME; adjust in index.php if CSE returns different keys.
 */
function getInvestAdvisors() {
    return callCSEAPI('/api/OtherServices/GetInvestAdvisors', 'POST', null, true);
}

function writeApiLog($message) {
    $logFile = defined('CSE_LOG_FILE') && CSE_LOG_FILE ? CSE_LOG_FILE : (CSE_STORAGE_PATH . '/api.log');
    if ($logFile) @file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}