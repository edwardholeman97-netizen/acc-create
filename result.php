<?php
// ==================== CONFIG ====================
define('API_BASE_URL', 'https://uat-cseapi.cse.lk');
define('API_USERNAME', 'SCTestUser');
define('API_PASSWORD', '2d26tF&M!cqS');
define('REQUEST_TIMEOUT', 60); // 60 seconds = 1 minute

$uniqueApiRefNo = time();

// ==================== SAMPLE DATA ====================
$sampleData = [
    "AccountID" => 0,
    "UserID" => "abc@gmail.com",
    "Title" => "MR.",
    "Initials" => "S.K.C.S.J.",
    "Surname" => "JAYAWARDANA",
    "NameDenoInitials" => "SUDUWELI KONDAGE CHANDIKA SUDANTHA",
    "MobileNo" => "0761234567",
    "TelphoneNo" => "",
    "Email" => "abc@gmail.com",
    "IdentificationProof" => "N",
    "NicNo" => "891234567V",
    "PassportNo" => "",
    "PassportExpDate" => "",
    "DateOfBirthday" => "1989/05/10",
    "Gender" => "M",
    "BrokerFirm" => "BMS",
    "ExitCDSAccount" => "Y",
    "CDSAccountNo" => "123511-LI",
    "TinNo" => "",
    "ResAddressStatus" => "Y",
    "ResAddressStatusDesc" => "2",
    "ResAddressLine01" => "79/7 KOLAMUNNA",
    "ResAddressLine02" => "AMPAN, KUDATHTHANAI",
    "ResAddressLine03" => "",
    "ResAddressTown" => "PILIYANDALA",
    "ResAddressDistrict" => "17",
    "Country" => "LK",
    "CorrAddressStatus" => "Y",
    "CorrAddressLine01" => "",
    "CorrAddressLine02" => "",
    "CorrAddressLine03" => "",
    "CorrAddressTown" => "",
    "CorrAddressDistrict" => "",
    "BankAccountNo" => "123456790",
    "BankCode" => "7056",
    "BankBranch" => "108",
    "BankAccountType" => "S",
    "EmployeStatus" => "N",
    "Occupation" => "FARMER",
    "NameOfEmployer" => "",
    "AddressOfEmployer" => "",
    "OfficePhoneNo" => "",
    "OfficeEmail" => "",
    "EmployeeComment" => "",
    "NameOfBusiness" => "",
    "AddressOfBusiness" => "",
    "OtherConnBusinessStatus" => "",
    "OtherConnBusinessDesc" => "",
    "ExpValueInvestment" => "2",
    "SourseOfFund" => "",
    "UsaPersonStatus" => "N",
    "UsaTaxIdentificationNo" => "",
    "FactaDeclaration" => "",
    "DualCitizenship" => "N",
    "DualCitizenCountry" => "",
    "DualCitizenPassport" => "",
    "IsPEP" => "N",
    "PepQ1" => "N",
    "PepQ1Details" => "",
    "PepQ2" => "N",
    "PepQ2Details" => "",
    "PepQ3" => "N",
    "PepQ3Details" => "",
    "PepQ4" => "N",
    "PepQ4Detailas" => "",
    "LatigationStatus" => "N",
    "LatigationDetails" => "",
    "Status" => "1",
    "EnterUser" => "abc@gmail.com",
    "SaveTable" => "",
    "SignData" => "",
    "StrockBrokerFirmName" => "",
    "CountryOfResidency" => "LK",
    "Nationality" => "LK",
    "ClientType" => "LI",
    "Residency" => "R",
    "IsLKPassport" => "",
    "InvestorId" => "12",
    "InvestmentOb" => "",
    "InvestmentStrategy" => "",
    "EnterDate" => "",
    "ApiUser" => "DIALOG",
    "ApiRefNo" => (string)$uniqueApiRefNo  // CHANGED to unique value!
];

// ==================== ENHANCED HELPER FUNCTION ====================
function sendRequest($url, $method = 'POST', $data = null, $headers = [], $returnResponse = true) {
    $ch = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => REQUEST_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true, // Get headers in response
        CURLINFO_HEADER_OUT => true, // Get request headers
        CURLOPT_VERBOSE => true, // Get verbose output
    ];
    
    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        if ($data !== null) {
            $options[CURLOPT_POSTFIELDS] = $data;
        }
    }
    
    if (!empty($headers)) {
        $options[CURLOPT_HTTPHEADER] = $headers;
    }
    
    curl_setopt_array($ch, $options);
    
    // Open buffer for verbose output
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
    
    // Get verbose output
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);
    
    // Separate headers and body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);
    
    curl_close($ch);
    
    $info = [
        'http_code' => $httpCode,
        'error' => $error,
        'errno' => $errno,
        'url' => $url,
        'response_headers' => $responseHeaders,
        'response_body' => $responseBody,
        'request_headers' => $requestHeaders,
        'verbose_log' => $verboseLog,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    return $returnResponse ? $responseBody : $info;
}

// ==================== ERROR HANDLING ====================
function handleCurlError($info) {
    $errors = [];
    
    if ($info['errno']) {
        $errors[] = "cURL Error #{$info['errno']}: {$info['error']}";
    }
    
    if ($info['http_code'] >= 400) {
        $errors[] = "HTTP Error: {$info['http_code']}";
    }
    
    if (empty($info['response_body']) && $info['http_code'] == 0) {
        $errors[] = "No response received (possible timeout after " . REQUEST_TIMEOUT . " seconds)";
    }
    
    return $errors;
}

// ==================== DISPLAY FUNCTIONS ====================
function displayResponse($title, $info) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo " $title\n";
    echo str_repeat("=", 80) . "\n\n";
    
    echo "Timestamp: {$info['timestamp']}\n";
    echo "URL: {$info['url']}\n";
    echo "HTTP Status: {$info['http_code']}\n";
    echo "cURL Error: " . ($info['error'] ?: 'None') . "\n";
    echo "cURL Error Number: " . ($info['errno'] ?: '0 (Success)') . "\n\n";
    
    // Display errors if any
    $errors = handleCurlError($info);
    if (!empty($errors)) {
        echo "ERRORS DETECTED:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
        echo "\n";
    }
    
    // Display request headers
    echo "REQUEST HEADERS:\n";
    echo str_repeat("-", 40) . "\n";
    echo $info['request_headers'] ?: 'No request headers captured';
    echo "\n\n";
    
    // Display response headers
    echo "RESPONSE HEADERS:\n";
    echo str_repeat("-", 40) . "\n";
    echo $info['response_headers'] ?: 'No response headers';
    echo "\n\n";
    
    // Display response body
    echo "RESPONSE BODY:\n";
    echo str_repeat("-", 40) . "\n";
    
    $body = $info['response_body'];
    $decoded = json_decode($body, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } elseif (strlen($body) > 0) {
        // Try to detect if it's XML
        if (strpos($body, '<?xml') === 0 || strpos($body, '<html') === 0) {
            echo htmlspecialchars($body);
        } else {
            echo $body;
        }
    } else {
        echo "(Empty response body)";
    }
    
    echo "\n\n";
    
    // Display verbose log if there are errors
    if (!empty($errors) && !empty($info['verbose_log'])) {
        echo "VERBOSE cURL LOG (for debugging):\n";
        echo str_repeat("-", 40) . "\n";
        echo $info['verbose_log'];
        echo "\n\n";
    }
}

// ==================== MAIN EXECUTION ====================
echo "API TEST SCRIPT WITH ENHANCED DEBUGGING\n";
echo "Timeout set to: " . REQUEST_TIMEOUT . " seconds\n";
echo "Start time: " . date('Y-m-d H:i:s') . "\n";

// ==================== STEP 1: GET BEARER TOKEN ====================
echo "\n" . str_repeat("=", 80) . "\n";
echo " STEP 1: GETTING BEARER TOKEN\n";
echo str_repeat("=", 80) . "\n";

$tokenUrl = API_BASE_URL . '/token';
$tokenData = http_build_query([
    'username' => API_USERNAME,
    'password' => API_PASSWORD,
    'grant_type' => 'password'
]);

$tokenHeaders = [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'
];

echo "Token URL: $tokenUrl\n";
echo "Token Data (password masked): username=" . API_USERNAME . "&password=********&grant_type=password\n";
echo "Starting token request...\n\n";

$tokenInfo = sendRequest($tokenUrl, 'POST', $tokenData, $tokenHeaders, false);
displayResponse("TOKEN RESPONSE", $tokenInfo);

$tokenArray = json_decode($tokenInfo['response_body'], true);
$bearerToken = $tokenArray['access_token'] ?? null;

if (!$bearerToken) {
    echo "\n" . str_repeat("!", 80) . "\n";
    echo " CRITICAL ERROR: Failed to get bearer token! Cannot proceed with user save.\n";
    echo str_repeat("!", 80) . "\n";
    
    // Try to extract error from response
    if (isset($tokenArray['error'])) {
        echo "\nOAuth Error: {$tokenArray['error']}\n";
        if (isset($tokenArray['error_description'])) {
            echo "Error Description: {$tokenArray['error_description']}\n";
        }
    }
    
    exit(1);
}

echo "✓ Bearer token obtained successfully\n";
echo "Token preview: " . substr($bearerToken, 0, 50) . "...\n";
echo "Token expires in: " . ($tokenArray['expires_in'] ?? 'Unknown') . " seconds\n";
echo "Token type: " . ($tokenArray['token_type'] ?? 'Unknown') . "\n";

// ==================== STEP 2: SEND USER DATA ====================
echo "\n" . str_repeat("=", 80) . "\n";
echo " STEP 2: SENDING USER DATA\n";
echo str_repeat("=", 80) . "\n";

$saveUserUrl = API_BASE_URL . '/api/User/SaveUser';
$jsonData = json_encode($sampleData, JSON_UNESCAPED_UNICODE);

$saveUserHeaders = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $bearerToken
];

echo "Save User URL: $saveUserUrl\n";
echo "JSON Data Size: " . strlen($jsonData) . " bytes\n";
echo "Number of fields: " . count($sampleData) . "\n";
echo "Starting user save request...\n\n";

// Display sample data summary
echo "DATA BEING SENT (First 10 fields):\n";
echo str_repeat("-", 40) . "\n";
$counter = 0;
foreach ($sampleData as $key => $value) {
    echo sprintf("%-25s: %s\n", $key, $value);
    $counter++;
    if ($counter >= 10) break;
}
echo "... (and " . (count($sampleData) - 10) . " more fields)\n\n";

$userInfo = sendRequest($saveUserUrl, 'POST', $jsonData, $saveUserHeaders, false);
displayResponse("SAVE USER RESPONSE", $userInfo);

// ==================== STEP 3: ANALYSIS ====================
echo "\n" . str_repeat("=", 80) . "\n";
echo " ANALYSIS & RESULTS\n";
echo str_repeat("=", 80) . "\n";

$success = false;
$message = '';
$responseBody = $userInfo['response_body'];
$decodedResponse = json_decode($responseBody, true);

if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
    // Check for common success patterns
    if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
        $success = true;
        $message = "API returned success: true";
    } elseif (isset($decodedResponse['status']) && $decodedResponse['status'] === 'success') {
        $success = true;
        $message = "API returned status: success";
    } elseif ($userInfo['http_code'] >= 200 && $userInfo['http_code'] < 300) {
        $success = true;
        $message = "HTTP status indicates success (2xx)";
    }
    
    // Extract any message
    if (isset($decodedResponse['message'])) {
        $message .= "\nMessage: " . $decodedResponse['message'];
    }
    if (isset($decodedResponse['error'])) {
        $message .= "\nError: " . $decodedResponse['error'];
    }
} else {
    $message = "Response is not valid JSON or is empty";
}

// Display summary
echo "SUMMARY:\n";
echo str_repeat("-", 40) . "\n";
echo "Token Request: " . ($bearerToken ? "✓ SUCCESS" : "✗ FAILED") . "\n";
echo "User Save Request HTTP Code: {$userInfo['http_code']}\n";
echo "Success Detection: " . ($success ? "✓ SUCCESS" : "✗ POSSIBLE FAILURE") . "\n";
echo "Response Type: " . (json_last_error() === JSON_ERROR_NONE ? "Valid JSON" : "Not JSON/Invalid") . "\n";
echo "\n";

// HTTP Status Code Interpretation
echo "HTTP STATUS INTERPRETATION:\n";
echo str_repeat("-", 40) . "\n";
switch (true) {
    case $userInfo['http_code'] == 0:
        echo "Code 0: No response received (timeout, DNS failure, or network issue)\n";
        echo "Possible causes: Server offline, firewall blocking, or timeout after " . REQUEST_TIMEOUT . " seconds\n";
        break;
    case $userInfo['http_code'] == 200:
        echo "200 OK: Request succeeded\n";
        break;
    case $userInfo['http_code'] == 201:
        echo "201 Created: Resource created successfully\n";
        break;
    case $userInfo['http_code'] == 400:
        echo "400 Bad Request: Invalid request data or malformed request\n";
        echo "Check the JSON structure and required fields\n";
        break;
    case $userInfo['http_code'] == 401:
        echo "401 Unauthorized: Invalid or expired token\n";
        echo "The bearer token might have expired or is invalid\n";
        break;
    case $userInfo['http_code'] == 403:
        echo "403 Forbidden: Valid token but insufficient permissions\n";
        break;
    case $userInfo['http_code'] == 404:
        echo "404 Not Found: API endpoint not found\n";
        echo "Check the URL: $saveUserUrl\n";
        break;
    case $userInfo['http_code'] == 422:
        echo "422 Unprocessable Entity: Validation errors in the data\n";
        echo "Check field values, formats, and constraints\n";
        break;
    case $userInfo['http_code'] == 429:
        echo "429 Too Many Requests: Rate limit exceeded\n";
        echo "Wait before trying again\n";
        break;
    case $userInfo['http_code'] >= 500:
        echo "{$userInfo['http_code']} Server Error: API server problem\n";
        echo "This is likely an issue on the server side\n";
        break;
    default:
        echo "HTTP {$userInfo['http_code']}: See HTTP specification for details\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo " END OF TEST - " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 80) . "\n";

// ==================== ADDITIONAL DEBUGGING INFO ====================
if (!empty($userInfo['error']) || $userInfo['http_code'] >= 400) {
    echo "\nADDITIONAL DEBUGGING TIPS:\n";
    echo "1. Check if the API endpoint is correct\n";
    echo "2. Verify the bearer token is valid and not expired\n";
    echo "3. Check network connectivity to: " . API_BASE_URL . "\n";
    echo "4. Validate the JSON structure\n";
    echo "5. Check for required fields in the sample data\n";
    echo "6. Contact API support with the error details above\n";
}

// Optional: Save logs to file
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'token_request' => $tokenInfo,
    'user_request' => $userInfo,
    'sample_data' => $sampleData
];

file_put_contents('api_test_log_' . date('Ymd_His') . '.json', json_encode($logData, JSON_PRETTY_PRINT));
echo "\nLog saved to: api_test_log_" . date('Ymd_His') . ".json\n";
?>

<!-- HTML output -->
<!DOCTYPE html>
<html>
<head>
    <title>API Test with Enhanced Debugging</title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 20px; background: #1a1a1a; color: #e0e0e0; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #444; }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #444; border-radius: 5px; background: #252525; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .info { color: #2196F3; }
        h1, h2, h3 { color: #fff; }
        h1 { border-bottom: 2px solid #444; padding-bottom: 10px; }
        .http-200 { color: #4CAF50; }
        .http-400 { color: #ff9800; }
        .http-500 { color: #f44336; }
        .http-0 { color: #9c27b0; }
        .timestamp { color: #888; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>API Test with Enhanced Debugging (Timeout: <?php echo REQUEST_TIMEOUT; ?>s)</h1>
    <div class="timestamp">Generated: <?php echo date('Y-m-d H:i:s'); ?></div>
    
    <div class="section">
        <h2>Step 1: Token Request</h2>
        <p><strong>Status:</strong> 
            <span class="<?php echo $bearerToken ? 'success' : 'error'; ?>">
                <?php echo $bearerToken ? 'SUCCESS' : 'FAILED'; ?>
            </span>
        </p>
        <p><strong>HTTP Status:</strong> 
            <span class="http-<?php echo $tokenInfo['http_code']; ?>">
                <?php echo $tokenInfo['http_code']; ?>
            </span>
        </p>
        <?php if ($bearerToken): ?>
            <p><strong>Token Preview:</strong> <?php echo htmlspecialchars(substr($bearerToken, 0, 50)) . '...'; ?></p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Step 2: Save User Request</h2>
        <p><strong>HTTP Status:</strong> 
            <span class="http-<?php echo $userInfo['http_code']; ?>">
                <?php echo $userInfo['http_code']; ?>
            </span>
        </p>
        
        <h3>Response Body:</h3>
        <pre><?php 
            if (!empty($userInfo['response_body'])) {
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedResponse)) {
                    echo htmlspecialchars(json_encode($decodedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } else {
                    echo htmlspecialchars($userInfo['response_body']);
                }
            } else {
                echo "(Empty response)";
            }
        ?></pre>
        
        <?php if (!empty($userInfo['error'])): ?>
            <h3 class="error">cURL Error:</h3>
            <pre><?php echo htmlspecialchars($userInfo['error']); ?></pre>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Sample Data Sent (First 10 fields):</h2>
        <pre><?php 
            $firstTen = array_slice($sampleData, 0, 10, true);
            echo htmlspecialchars(json_encode($firstTen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo "\n\n... and " . (count($sampleData) - 10) . " more fields";
        ?></pre>
    </div>
    
    <div class="section">
        <h2>Analysis Results</h2>
        <p><strong>Overall Status:</strong> 
            <span class="<?php echo $success ? 'success' : ($userInfo['http_code'] >= 400 || $userInfo['http_code'] == 0 ? 'error' : 'warning'); ?>">
                <?php 
                    if ($success) echo 'SUCCESS';
                    elseif ($userInfo['http_code'] >= 400 || $userInfo['http_code'] == 0) echo 'FAILED';
                    else echo 'UNCLEAR - Check response';
                ?>
            </span>
        </p>
        <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($message)); ?></p>
    </div>
</body>
</html>