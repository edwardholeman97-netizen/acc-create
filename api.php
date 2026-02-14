<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ==================== CONFIG ====================
define('API_BASE_URL', 'https://uat-cseapi.cse.lk');
define('API_USERNAME', 'SCTestUser');
define('API_PASSWORD', '2d26tF&M!cqS');

$response = ['success' => false, 'message' => '', 'accountId' => null, 'step' => null];

try {
    liveLog('API Request Started');
    
    // ==================== HANDLE INCOMING DATA ====================
    $formData = [];
    $hasFiles = false;
    $step = 1; // Default to step 1
    
    // Check if it's multipart/form-data (file upload)
    if ($_SERVER['CONTENT_TYPE'] && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        liveLog('Request is multipart/form-data (with files)');
        $hasFiles = true;
        
        // Extract form fields from POST (excluding files)
        foreach ($_POST as $key => $value) {
            $formData[$key] = $value;
        }
        
        // Get step from form data
        $step = isset($formData['form_step']) ? (int)$formData['form_step'] : 1;
        
    } else {
        // It's JSON request
        liveLog('Request is JSON');
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid or empty JSON data');
        }
        
        $formData = $input['formData'] ?? $input;
        $step = isset($formData['form_step']) ? (int)$formData['form_step'] : 
                (isset($input['step']) ? (int)$input['step'] : 1);
    }
    
    if (empty($formData)) {
        throw new Exception('No form data received');
    }
    
    liveLog("Processing Step: $step");
    liveLog('Form data keys: ' . implode(', ', array_keys($formData)));
    
    // ==================== AUTHENTICATE ====================
    liveLog('Step 0: Authentication');
    $token = getAuthToken();
    if (!$token) throw new Exception('Authentication failed');
    
    // ==================== ROUTE TO APPROPRIATE STEP ====================
    switch ($step) {
        case 1:
            // STEP 1: Create New Account (AccountID = 0)
            $response = handleStep1($token, $formData);
            break;
            
        case 2:
            // STEP 2: Update with Address, Employment, Bank Details
            $response = handleStep2($token, $formData);
            break;
            
        case 3:
            // STEP 3: Update with Compliance, Source of Funds, Documents
            $response = handleStep3($token, $formData, $hasFiles);
            break;
            
        default:
            throw new Exception('Invalid step: ' . $step);
    }
    
} catch (Exception $e) {
    liveLog('Error: ' . $e->getMessage(), 'error');
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);












// ==================== STEP 1: CREATE NEW ACCOUNT ====================
function handleStep1($token, $formData) {
    liveLog('=== STEP 1: Create New Account ===');
    
    // IMPORTANT: AccountID MUST be 0 for new account creation
    $formData['AccountID'] = 0;
    
    // Generate unique identifiers for new account
    if (empty($formData['UserID'])) {
        $formData['UserID'] = generateUserId();
    }
    
    if (empty($formData['ApiRefNo'])) {
        $formData['ApiRefNo'] = generateReferenceNumber();
    }
    
    // Set default status for new submission
    $formData['Status'] = '1'; // 1 = Submit (not 4 = Re-submit)
    
    // Prepare ONLY Step 1 fields (Personal + Identification + Broker)
    $userData = prepareStep1Data($formData);
    
    // Save to CSE API
    $accountId = saveUserData($token, $userData);
    
    if (!$accountId) {
        throw new Exception('Failed to create account');
    }
    
    liveLog("Account created successfully with ID: $accountId");
    
    // Store in session steps (if needed)
    session_start();
    $_SESSION['cse_account_id'] = $accountId;
    $_SESSION['cse_user_id'] = $formData['UserID'];
    $_SESSION['cse_api_ref_no'] = $formData['ApiRefNo'];
    
    return [
        'success' => true,
        'message' => 'Account created successfully',
        'accountId' => $accountId,
        'userId' => $formData['UserID'],
        'apiRefNo' => $formData['ApiRefNo'],
        'step' => 1
    ];
}








// ==================== STEP 2: UPDATE WITH ADDRESS, EMPLOYMENT, BANK ====================
function handleStep2($token, $formData) {
    liveLog('=== STEP 2: Update Account Details ===');
    
    // VALIDATE: Account ID must be provided
    if (empty($formData['AccountID']) && empty($formData['accountId'])) {
        throw new Exception('Account ID is required for Step 2');
    }
    
    // Get Account ID from various possible sources
    $accountId = $formData['AccountID'] ?? $formData['accountId'] ?? 0;
    
    // Try to get from session if not provided
    if ($accountId == 0) {
        session_start();
        $accountId = $_SESSION['cse_account_id'] ?? 0;
        $formData['UserID'] = $formData['UserID'] ?? $_SESSION['cse_user_id'] ?? generateUserId();
        $formData['ApiRefNo'] = $formData['ApiRefNo'] ?? $_SESSION['cse_api_ref_no'] ?? generateReferenceNumber();
    }
    
    if ($accountId == 0) {
        throw new Exception('Valid Account ID is required for update');
    }
    
    liveLog("Updating Account ID: $accountId");
    
    // Set Account ID in form data
    $formData['AccountID'] = $accountId;
    
    // Status 1 for update (not 0 - new)
    $formData['Status'] = '1';
    
    // Prepare ONLY Step 2 fields (Address + Employment + Bank)
    $userData = prepareStep2Data($formData);
    
    // Save to CSE API (UPDATE existing record)
    $updatedAccountId = saveUserData($token, $userData);
    
    if (!$updatedAccountId) {
        throw new Exception('Failed to update account');
    }
    
    liveLog("Account updated successfully: $updatedAccountId");
    
    return [
        'success' => true,
        'message' => 'Account details updated successfully',
        'accountId' => $accountId,
        'step' => 2
    ];
}








// ==================== STEP 3: COMPLIANCE, FUNDS & DOCUMENTS ====================
function handleStep3($token, $formData, $hasFiles) {
    liveLog('=== STEP 3: Finalize Account ===');
    
    // VALIDATE: Account ID must be provided
    if (empty($formData['AccountID']) && empty($formData['accountId'])) {
        throw new Exception('Account ID is required for Step 3');
    }
    
    // Get Account ID from various possible sources
    $accountId = $formData['AccountID'] ?? $formData['accountId'] ?? 0;
    
    // Try to get from session if not provided
    if ($accountId == 0) {
        session_start();
        $accountId = $_SESSION['cse_account_id'] ?? 0;
        $formData['UserID'] = $formData['UserID'] ?? $_SESSION['cse_user_id'] ?? generateUserId();
        $formData['ApiRefNo'] = $formData['ApiRefNo'] ?? $_SESSION['cse_api_ref_no'] ?? generateReferenceNumber();
    }
    
    if ($accountId == 0) {
        throw new Exception('Valid Account ID is required for finalization');
    }
    
    liveLog("Finalizing Account ID: $accountId");
    
    // Set Account ID in form data
    $formData['AccountID'] = $accountId;
    
    $results = [
        'user_saved' => false,
        'source_funds_saved' => false,
        'images_uploaded' => false
    ];
    
    // 1. Update User with Compliance data (PEP, Litigation, etc)
    $userData = prepareStep3Data($formData);
    $updatedAccountId = saveUserData($token, $userData);
    $results['user_saved'] = !empty($updatedAccountId);
    
    // 2. Save Source of Funds
    $results['source_funds_saved'] = saveSourceFunds($token, $accountId, $formData);
    
    // 3. Upload Images (if any)
    if ($hasFiles && !empty($_FILES)) {
        $results['images_uploaded'] = uploadImages($token, $accountId, $formData);
    } else {
        $results['images_uploaded'] = true; // No files to upload
    }
    
    // Clear session data after completion
    session_start();
    unset($_SESSION['cse_account_id']);
    unset($_SESSION['cse_user_id']);
    unset($_SESSION['cse_api_ref_no']);
    
    return [
        'success' => true,
        'message' => 'Account finalized successfully',
        'accountId' => $accountId,
        'step' => 3,
        'details' => $results
    ];
}








// ==================== DATA PREPARATION FUNCTIONS ====================

/**
 * STEP 1: Personal Information + Identification + Broker
 * Fields needed for initial account creation
 */
function prepareStep1Data($formData) {
    $data = [
        // MANDATORY: AccountID = 0 for new account
        'AccountID' => 0,
        
        // Personal Information
        'UserID' => $formData['UserID'] ?? generateUserId(),
        'Title' => $formData['Title'] ?? '',
        'Initials' => $formData['Initials'] ?? '',
        'Surname' => $formData['Surname'] ?? '',
        'NameDenoInitials' => $formData['NameDenoInitials'] ?? '',
        'MobileNo' => $formData['MobileNo'] ?? '',
        'TelphoneNo' => $formData['TelphoneNo'] ?? '',
        'Email' => $formData['Email'] ?? '',

        // Residential Address
        'ResAddressStatus' => $formData['ResAddressStatus'] ?? 'Y',
        'RES_ADDRESS_STATUS' => $formData['ResAddressStatus'] ?? 'Y',
        'ResAddressStatusDesc' => $formData['ResAddressStatusDesc'] ?? 'Y',
        'ResAddressLine01' => $formData['ResAddressLine01'] ?? '',
        'ResAddressLine02' => $formData['ResAddressLine02'] ?? '',
        'ResAddressLine03' => $formData['ResAddressLine03'] ?? '',
        'ResAddressTown' => $formData['ResAddressTown'] ?? '',
        'ResAddressDistrict' => $formData['ResAddressDistrict'] ?? '',
        'Country' => $formData['Country'] ?? '',
        
        // Identification
        'IdentificationProof' => mapIdentificationProof($formData['IdentificationProof'] ?? ''),
        'NicNo' => $formData['NicNo'] ?? '',
        'PassportNo' => $formData['PassportNo'] ?? '',
        'PassportExpDate' => formatDateForAPI($formData['PassportExpDate'] ?? ''),
        'DateOfBirthday' => formatDateForAPI($formData['DateOfBirthday'] ?? ''),
        'Gender' => $formData['Gender'] ?? '',

        // Bank Information
        'BankAccountNo' => $formData['BankAccountNo'] ?? '',
        'BankCode' => $formData['BankCode'] ?? '',
        'BankBranch' => $formData['BankBranch'] ?? '',
        'BankAccountType' => $formData['BankAccountType'] ?? 'I',
        
        // Broker Information
        'BrokerFirm' => $formData['BrokerFirm'] ?? '',
        'ExitCDSAccount' => $formData['ExitCDSAccount'] ?? 'N',
        'CDSAccountNo' => $formData['CDSAccountNo'] ?? '',
        'TinNo' => $formData['TinNo'] ?? '',

        // Additional Fields
        'CountryOfResidency' => $formData['CountryOfResidency'] ?? '',
        'Nationality' => $formData['Nationality'] ?? '',
        'Residency' => $formData['Residency'] ?? 'R',
        'IsLKPassport' => $formData['IsLKPassport'] ?? 'N',
        
        // System Fields
        'Status' => '1', // 1 = Submit
        'EnterUser' => $formData['Email'] ?? 'SYSTEM',
        'ApiUser' => 'DIALOG',
        'ApiRefNo' => '',
        
        // Client Type (default to FI for your use case)
        'ClientType' => $formData['ClientType'] ?? 'FI',
    ];
    
    return $data;
}





/**
 * STEP 2: Address + Employment + Bank Information
 */
function prepareStep2Data($formData) {
    $data = [
        // MANDATORY: Use existing Account ID
        'AccountID' => $formData['AccountID'] ?? 0,
        'UserID' => $formData['UserID'] ?? '',
        
        // Residential Address
        'ResAddressStatus' => $formData['ResAddressStatus'] ?? 'Y',
        'RES_ADDRESS_STATUS' => $formData['ResAddressStatus'] ?? 'Y',
        'ResAddressStatusDesc' => $formData['ResAddressStatusDesc'] ?? '',
        'ResAddressLine01' => $formData['ResAddressLine01'] ?? '',
        'ResAddressLine02' => $formData['ResAddressLine02'] ?? '',
        'ResAddressLine03' => $formData['ResAddressLine03'] ?? '',
        'ResAddressTown' => $formData['ResAddressTown'] ?? '',
        'ResAddressDistrict' => $formData['ResAddressDistrict'] ?? '',
        'Country' => $formData['Country'] ?? '',
        
        // Correspondence Address (if different)
        'CorrAddressStatus' => $formData['CorrAddressStatus'] ?? 'Y',
        'CorrAddressLine01' => $formData['CorrAddressLine01'] ?? '',
        'CorrAddressLine02' => $formData['CorrAddressLine02'] ?? '',
        'CorrAddressLine03' => $formData['CorrAddressLine03'] ?? '',
        'CorrAddressTown' => $formData['CorrAddressTown'] ?? '',
        'CorrAddressDistrict' => $formData['CorrAddressDistrict'] ?? '',
        
        // Bank Information
        'BankAccountNo' => $formData['BankAccountNo'] ?? '',
        'BankCode' => $formData['BankCode'] ?? '',
        'BankBranch' => $formData['BankBranch'] ?? '',
        'BankAccountType' => $formData['BankAccountType'] ?? 'I',
        
        // Employment
        'EmployeStatus' => $formData['EmployeStatus'] ?? '',
        'Occupation' => $formData['Occupation'] ?? '',
        'NameOfEmployer' => $formData['NameOfEmployer'] ?? '',
        'AddressOfEmployer' => $formData['AddressOfEmployer'] ?? '',
        'OfficePhoneNo' => $formData['OfficePhoneNo'] ?? '',
        'OfficeEmail' => $formData['OfficeEmail'] ?? '',
        'EmployeeComment' => $formData['EmployeeComment'] ?? '',
        'NameOfBusiness' => $formData['NameOfBusiness'] ?? '',
        'AddressOfBusiness' => $formData['AddressOfBusiness'] ?? '',
        'OtherConnBusinessStatus' => $formData['OtherConnBusinessStatus'] ?? 'N',
        'OtherConnBusinessDesc' => $formData['OtherConnBusinessDesc'] ?? '',
        
        // Additional Fields
        'CountryOfResidency' => $formData['CountryOfResidency'] ?? '',
        'Nationality' => $formData['Nationality'] ?? '',
        'Residency' => $formData['Residency'] ?? 'R',
        'IsLKPassport' => $formData['IsLKPassport'] ?? 'N',
        
        // System
        'ApiRefNo' => $formData['ApiRefNo'] ?? '',
        'Status' => '1'
    ];
    
    return $data;
}





/**
 * STEP 3: Compliance + Investment + Source of Funds
 */
function prepareStep3Data($formData) {
    $data = [
        // MANDATORY: Use existing Account ID
        'AccountID' => $formData['AccountID'] ?? 0,
        'UserID' => $formData['UserID'] ?? '',
        
        // Investment
        'ExpValueInvestment' => $formData['ExpValueInvestment'] ?? '1',
        'InvestorId' => $formData['InvestorId'] ?? '',
        'InvestmentOb' => $formData['InvestmentOb'] ?? '',
        'InvestmentStrategy' => $formData['InvestmentStrategy'] ?? '',
        
        // USA & Compliance
        'UsaPersonStatus' => $formData['UsaPersonStatus'] ?? 'N',
        'UsaTaxIdentificationNo' => $formData['UsaTaxIdentifierNo'] ?? '',
        'FactaDeclaration' => $formData['FactaDeclaration'] ?? 'N',
        'DualCitizenship' => $formData['DualCitizenship'] ?? 'N',
        'DualCitizenCountry' => $formData['DualCitizenCountry'] ?? '',
        'DualCitizenPassport' => $formData['DualCitizenPassport'] ?? '',
        
        // PEP
        'IsPEP' => $formData['IsPEP'] ?? 'N',
        'PepQ1' => $formData['PEP_Q1'] ?? 'N',
        'PepQ1Details' => $formData['PEP_Q1_Details'] ?? '',
        'PepQ2' => $formData['PEP_Q2'] ?? 'N',
        'PepQ2Details' => $formData['PEP_Q2_Details'] ?? '',
        'PepQ3' => $formData['PEP_Q3'] ?? 'N',
        'PepQ3Details' => $formData['PEP_Q3_Details'] ?? '',
        'PepQ4' => $formData['PEP_Q4'] ?? 'N',
        'PepQ4Detailas' => $formData['PEP_Q4_Details'] ?? '', // Note API typo
        
        // Litigation
        'LatigationStatus' => $formData['LitigationStatus'] ?? 'N',
        'LatigationDetails' => $formData['LitigationDetails'] ?? '',
        
        // System
        'ApiRefNo' => $formData['ApiRefNo'] ?? '',
        'Status' => '1'
    ];
    
    return $data;
}










// ==================== HELPER FUNCTIONS ====================

/**
 * Map form ID proof to CSE API expected values
 */
function mapIdentificationProof($value) {
    $map = [
        'NIC' => 'N',
        'Passport' => 'P',
        '' => 'N' // Default to NIC
    ];
    
    return $map[$value] ?? 'N';
}



/**
 * Generate unique User ID
 */
function generateUserId() {
    return 'USER_' . date('Ymd') . '_' . uniqid();
}



/**
 * Generate reference number (timestamp based)
 */
function generateReferenceNumber() {
    return 'SAMPATH_' . time() . '_' . rand(100, 999);
}




/**
 * Format date for API (YYYY/MM/DD)
 */
function formatDateForAPI($date) {
    if (empty($date)) return '';
    
    // Convert from YYYY-MM-DD to YYYY/MM/DD
    return str_replace('-', '/', $date);
}





/**
 * Get authentication token
 */
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
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $result) {
        $response = json_decode($result, true);
        if (isset($response['access_token'])) {
            liveLog('Auth token received');
            return $response['access_token'];
        }
    }
    
    liveLog("Auth failed. HTTP: $httpCode", 'error');
    return null;
}





/**
 * Save user data to CSE API
 */
function saveUserData($token, $userData) {
    $url = API_BASE_URL . '/api/User/SaveUser';
    
    liveLog('Saving user data: ' . json_encode([
        'AccountID' => $userData['AccountID'] ?? 0,
        'UserID' => $userData['UserID'] ?? '',
        'fields' => count($userData)
    ]));
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($userData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=utf-8'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    liveLog("SaveUser Response HTTP: $httpCode");

    if ($httpCode === 200 && $result) {
        $response = json_decode($result, true);
        
        // Response could be numeric (Account ID) or array with AccountID
        if (is_numeric($response)) {
            return (int)$response;
        }
        
        if (isset($response['AccountID'])) {
            return (int)$response['AccountID'];
        }
        
        // If we get success but no AccountID, return the AccountID we sent
        if ($userData['AccountID'] > 0) {
            return $userData['AccountID'];
        }
    }
    
    liveLog("SaveUser failed. HTTP: $httpCode Response: $result", 'error');
    return null;
}






/**
 * Save source of funds
 */
function saveSourceFunds($token, $accountId, $formData) {
    liveLog('Saving source of funds for Account ID: ' . $accountId);
    
    $payload = [
        'AccountID' => $accountId,
        'UserID' => $formData['UserID'] ?? '',
        'SaveTable' => 'F'
    ];
    
    // Map source of fund selection (1-11) to Item01-Item11
    $sourceFundValue = $formData['SourseOfFund'] ?? '';
    if (!empty($sourceFundValue) && is_numeric($sourceFundValue)) {
        $itemNumber = str_pad($sourceFundValue, 2, '0', STR_PAD_LEFT);
        $payload['Item' . $itemNumber] = 'Y';
    }
    
    $url = API_BASE_URL . '/api/User/SaveSourceFunds';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=utf-8'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 200);
}




/**
 * Upload images
 */
function uploadImages($token, $accountId, $formData) {
    liveLog('Uploading images for Account ID: ' . $accountId);
    
    $imageTypeMap = [
        'selfie_upload' => 1,
        'nic_front_upload' => 2,
        'nic_back_upload' => 3,
        'passport_upload' => 4
    ];
    
    $success = true;
    
    foreach ($_FILES as $fieldName => $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $imageType = $imageTypeMap[$fieldName] ?? 0;
        if ($imageType === 0) continue;
        
        $imageData = base64_encode(file_get_contents($file['tmp_name']));
        
        $payload = [
            'AccountID' => $accountId,
            'ImageType' => $imageType,
            'Image' => $imageData,
            'UserID' => $formData['UserID'] ?? ''
        ];
        
        $url = API_BASE_URL . '/api/ImageUpload/UploadImageOnebyOne';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json; charset=utf-8'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $success = false;
            liveLog("Failed to upload $fieldName. HTTP: $httpCode", 'error');
        }
    }
    
    return $success;
}

/**
 * Logging function
 */
function liveLog($message, $type = 'info') {
    $logFile = __DIR__ . '/api_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
