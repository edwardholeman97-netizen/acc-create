<?php

session_start();

// resource.php - CSE API Resource Endpoints
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ==================== CONFIG ====================
define('API_BASE_URL', 'https://uat-cseapi.cse.lk');
define('API_USERNAME', 'SCTestUser');
define('API_PASSWORD', '2d26tF&M!cqS');

// Response array
$response = ['success' => false, 'message' => '', 'data' => []];

// ==================== AUTH FUNCTION ====================
function getAuthToken() {
    $url = API_BASE_URL . '/token';
    
    $data = [
        'username' => API_USERNAME,
        'password' => API_PASSWORD,
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




// ==================== CURL WRAPPER ====================
function callCSEAPI($endpoint, $method = 'POST', $data = null, $requiresAuth = true) {

    $url = API_BASE_URL . $endpoint;
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
            break;

        case 'verifyAccount':
            $accountId = $_GET['accountId'] ?? $_POST['accountId'] ?? '';
            if (empty($accountId)) {
                throw new Exception('Account ID is required');
            }
            $token = getAuthToken();
            $result = verifyAccount($token, $accountId);
            $response['success'] = !empty($result);
            $response['data'] = $result;
            break;
            
        // Add more cases as we build functions
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    


    $response['success'] = $result['success'] ?? false;
    $response['data'] = $result['data'] ?? [];
    $response['http_code'] = $result['http_code'] ?? 0;
    
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
 * 
 * @return array API response with success status and title data
 */
function getTitles() {
    return callCSEAPI('/api/OtherServices/GetTitle', 'POST', null, true);
}



// Add this function to verify existing accounts
function verifyAccount($token, $accountId) {
    // You'll need to check if CSE has an endpoint to verify accounts
    // For now, we'll assume it exists and return true if valid
    $url = API_BASE_URL . '/api/User/GetUser?AccountID=' . $accountId;
    
    $result = callCSEAPI('/api/User/GetUser?AccountID=' . $accountId, 'GET', null, true);
    
    if ($result['success'] && $result['http_code'] === 200) {
        return $result['data'] ?? null;
    }
    
    return null;
}




function writeApiLog($message) {
    $logFile = __DIR__ . '/live_api_log.txt';
    file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
}


