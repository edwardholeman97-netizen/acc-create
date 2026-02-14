<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (CORS_ALLOW_ORIGIN ?: '*'));
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Full API field spec: https://docs.google.com/spreadsheets/d/1RmWTSGOAT9E408jGtw7_Gm6FQeGGI_njWcJ2uqcE070/edit?usp=sharing
// Null? N = nullable, Y = required. Flow: token → SaveUser → documents one by one.

$response = ['success' => false, 'message' => '', 'accountId' => null];

try {
    liveLog('API Request Started');
    
    // ==================== FIX: HANDLE BOTH JSON AND FORM-DATA ====================
    $formData = [];
    $hasFiles = false;
    
    // Check if it's multipart/form-data (file upload)
    if ($_SERVER['CONTENT_TYPE'] && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        liveLog('Request is multipart/form-data (with files)');
        $hasFiles = true;
        
        // Extract form fields from POST (excluding files)
        foreach ($_POST as $key => $value) {
            $formData[$key] = $value;
        }
        
        // Add UserID if not provided
        if (!isset($formData['UserID'])) {
            $formData['UserID'] = generateUserId();
        }
        
    } else {
        // It's JSON request
        liveLog('Request is JSON');
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['formData'])) {
            throw new Exception('Invalid or empty JSON data');
        }
        
        $formData = $input['formData'];
    }
    
    if (empty($formData)) {
        throw new Exception('No form data received');
    }
    
    liveLog('Form data processed successfully');
    // ==================== END FIX ====================

    // Validate required fields (doc Null? Y = not nullable = required)
    validateRequiredSaveUserFields($formData);

    // Step 1: Authenticate
    liveLog('Step 1: Authentication');
    $token = getAuthToken();
    if (!$token) throw new Exception('Authentication failed');

    // Step 2: Prepare user data
    liveLog('Step 2: Preparing user data');
    $userData = prepareUserData($formData);

    // Step 3: Save user
    liveLog('Step 3: Saving user data to CSE');
    $saveResult = saveUserData($token, $userData);
    $accountId = $saveResult['accountId'] ?? null;
    if (!$accountId) {
        $msg = trim($saveResult['error'] ?? '');
        throw new Exception($msg ?: 'Failed to save user data');
    }

    // Step 4: Upload images (if any)
    liveLog('Step 4: Uploading images');
    $imageUploadSuccess = false;

    if ($hasFiles && !empty($_FILES)) {
        // Form was submitted with files
        $imageUploadSuccess = uploadImages($token, $accountId, $formData);
        liveLog('Image upload completed: ' . ($imageUploadSuccess ? 'Success' : 'Partial/Failed'));
    } else {
        // No files in this request
        liveLog('No image files in this request');
        $imageUploadSuccess = true;
    }

    // Step 5: Save source of funds
    liveLog('Step 5: Saving source of funds');
    $sourceFundsSuccess = saveSourceFunds($token, $accountId, $formData);

    // Success response
    $response['success'] = true;
    $response['message'] = 'Account created successfully';
    $response['accountId'] = $accountId;
    $response['sourceFundsSaved'] = $sourceFundsSuccess;
    $response['imagesUploaded'] = $imageUploadSuccess;
    liveLog('All operations completed');

} catch (Exception $e) {
    liveLog('Error: ' . $e->getMessage(), 'error');
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);









// ==================== CORE FUNCTIONS ====================

/**
 * Get authentication token
 */
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
 * Prepare user data for CSE API (SaveUser).
 * Field list, types, nullability: see API doc spreadsheet (link in CONFIG above).
 * Dates: yyyy/mm/dd per doc. STATUS: 1=submit, 4=resubmit. GetTitle/GetBroker/GetDistrict/GetCountry/GetBank/GetBankBranch/GetInvestAdvisors for dropdowns.
 */
function prepareUserData($formData) {
    liveLog('Mapping form data to CSE fields');
    $idProof = $formData['IdentificationProof'] ?? 'P';
    // Doc: when IdentificationProof is P (Passport), NIC_NO must be empty
    $nicNo = ($idProof === 'P') ? '' : ($formData['NicNo'] ?? '');
    
    $mappedData = [
        // Personal (doc: TITLE GetTitle, INITIALS 15, SURNAME 50, NAMES_DENO_INITIALS 160, MOBILE 16, EMAIL 100)
        'AccountID' => 0,
        'UserID' => $formData['UserID'] ?? generateUserId(),
        'Title' => $formData['Title'] ?? '',
        'Initials' => substr($formData['Initials'] ?? '', 0, 15),
        'Surname' => substr($formData['Surname'] ?? '', 0, 50),
        'NameDenoInitials' => substr($formData['NameDenoInitials'] ?? '', 0, 160),
        'MobileNo' => substr($formData['MobileNo'] ?? '', 0, 16),
        'TelphoneNo' => substr($formData['TelphoneNo'] ?? '', 0, 16),
        'Email' => substr($formData['Email'] ?? '', 0, 100),
        
        // Identification (doc: P=Passport/N=NIC, NIC_NO empty when P, DATE yyyy/mm/dd)
        'IdentificationProof' => $idProof,
        'NicNo' => $nicNo,
        'PassportNo' => $formData['PassportNo'] ?? '',
        'PassportExpDate' => formatDateForAPI($formData['PassportExpDate'] ?? ''),
        'DateOfBirthday' => formatDateForAPI($formData['DateOfBirthday'] ?? ''),
        'Gender' => $formData['Gender'] ?? '',
        
        // Broker Information - NOTE: Must be 3-char code from getBrokers()
        // Broker (doc: STOCK_BROKER_FIRM 3 chars GetBroker, EXIST_CDS_ACCOUNT Y/N, CDS_ACCOUNT_NO 20, TIN 20)
        'BrokerFirm' => substr($formData['BrokerFirm'] ?? '', 0, 3),
        'ExitCDSAccount' => $formData['ExitCDSAccount'] ?? 'N',
        'CDSAccountNo' => substr($formData['CDSAccountNo'] ?? '', 0, 20),
        'TinNo' => substr($formData['TinNo'] ?? '', 0, 20),
        
        // Residential Address (doc: RES_ADDRESS_* 30/15, DISTRICT NUMBER(2) GetDistrict, COUNTRY GetCountry)
        'ResAddressStatus' => $formData['ResAddressStatus'] ?? 'Y',
        'ResAddressStatusDesc' => $formData['ResAddressStatusDesc'] ?? '',
        'ResAddressLine01' => substr($formData['ResAddressLine01'] ?? '', 0, 30),
        'ResAddressLine02' => substr($formData['ResAddressLine02'] ?? '', 0, 30),
        'ResAddressLine03' => substr($formData['ResAddressLine03'] ?? '', 0, 15),
        'ResAddressTown' => substr($formData['ResAddressTown'] ?? '', 0, 15),
        'ResAddressDistrict' => $formData['ResAddressDistrict'] ?? '',
        'Country' => substr($formData['Country'] ?? '', 0, 4),
        
        // Correspondence Address (doc: optional; same structure)
        'CorrAddressStatus' => $formData['CorrAddressStatus'] ?? 'Y',
        'CorrAddressLine01' => substr($formData['CorrAddressLine01'] ?? '', 0, 30),
        'CorrAddressLine02' => substr($formData['CorrAddressLine02'] ?? '', 0, 30),
        'CorrAddressLine03' => substr($formData['CorrAddressLine03'] ?? '', 0, 15),
        'CorrAddressTown' => substr($formData['CorrAddressTown'] ?? '', 0, 15),
        'CorrAddressDistrict' => $formData['CorrAddressDistrict'] ?? '',
        
        // Bank (doc: BANK_ACCOUNT_NO 12, BANK_CODE 4 GetBank, BANK_BRANCH 4 GetBankBranch, BANK_ACCOUNT_TYPE I/C)
        'BankAccountNo' => substr($formData['BankAccountNo'] ?? '', 0, 12),
        'BankCode' => substr($formData['BankCode'] ?? '', 0, 4),
        'BankBranch' => substr($formData['BankBranch'] ?? '', 0, 4),
        'BankAccountType' => $formData['BankAccountType'] ?? 'I',
        
        // Employment (doc: EMPLOYE_STATUS Y/N/S/T, OCCUPATION 50, NAME_OF_EMPLOYER 100, etc.)
        'EmployeStatus' => $formData['EmployeStatus'] ?? '',
        'Occupation' => substr($formData['Occupation'] ?? '', 0, 50),
        'NameOfEmployer' => $formData['NameOfEmployer'] ?? '',
        'AddressOfEmployer' => $formData['AddressOfEmployer'] ?? '',
        'OfficePhoneNo' => $formData['OfficePhoneNo'] ?? '',
        'OfficeEmail' => $formData['OfficeEmail'] ?? '',
        'EmployeeComment' => $formData['EmployeeComment'] ?? '',
        'NameOfBusiness' => $formData['NameOfBusiness'] ?? '',
        'AddressOfBusiness' => $formData['AddressOfBusiness'] ?? '',
        'OtherConnBusinessStatus' => $formData['OtherConnBusinessStatus'] ?? 'N',
        'OtherConnBusinessDesc' => $formData['OtherConnBusinessDesc'] ?? '',
        
        // Investment & Funds
        'ExpValueInvestment' => $formData['ExpValueInvestment'] ?? '1',
        'SourseOfFund' => $formData['SourseOfFund'] ?? '',
        
        // USA & Compliance
        'UsaPersonStatus' => $formData['UsaPersonStatus'] ?? 'N',
        'UsaTaxIdentificationNo' => $formData['UsaTaxIdentifierNo'] ?? '',
        'FactaDeclaration' => $formData['FactaDeclaration'] ?? 'N',
        'DualCitizenship' => $formData['DualCitizenship'] ?? 'N',
        'DualCitizenCountry' => $formData['DualCitizenCountry'] ?? '',
        'DualCitizenPassport' => $formData['DualCitizenPassport'] ?? '',
        
        // PEP - Your form uses underscores, API uses camelCase
        'IsPEP' => $formData['IsPEP'] ?? 'N',
        'PepQ1' => $formData['PEP_Q1'] ?? 'N',
        'PepQ1Details' => $formData['PEP_Q1_Details'] ?? '',
        'PepQ2' => $formData['PEP_Q2'] ?? 'N',
        'PepQ2Details' => $formData['PEP_Q2_Details'] ?? '',
        'PepQ3' => $formData['PEP_Q3'] ?? 'N',
        'PepQ3Details' => $formData['PEP_Q3_Details'] ?? '',
        'PepQ4' => $formData['PEP_Q4'] ?? 'N',
        'PepQ4Detailas' => $formData['PEP_Q4_Details'] ?? '', // Note: API has typo "Detailas"
        
        // Litigation - Your form uses correct spelling, API has typo "Latigation"
        'LatigationStatus' => $formData['LitigationStatus'] ?? 'N',
        'LatigationDetails' => $formData['LitigationDetails'] ?? '',
        
        // System Fields
        'Status' => $formData['Status'] ?? '1',
        'EnterUser' => $formData['EnterUser'] ?? 'SYSTEM',
        'SaveTable' => $formData['SaveTable'] ?? 'U',
        'SignData' => $formData['SignData'] ?? '',
        
        // Additional Fields
        'StrockBrokerFirmName' => $formData['BrokerFirm'] ?? '', // Same as BrokerFirm
        'CountryOfResidency' => $formData['CountryOfResidency'] ?? '',
        'Nationality' => $formData['Nationality'] ?? '',
        'ClientType' => $formData['ClientType'] ?? 'FI',
        'Residency' => $formData['Residency'] ?? 'R',
        'IsLKPassport' => $formData['IsLKPassport'] ?? 'N',
        'InvestorId' => $formData['InvestorId'] ?? '',
        'InvestmentOb' => $formData['InvestmentOb'] ?? '',
        'InvestmentStrategy' => $formData['InvestmentStrategy'] ?? '',
        'EnterDate' => formatDateForAPI($formData['EnterDate'] ?? date('Y-m-d')),
        'ApiUser' => $formData['ApiUser'] ?? 'DIALOG',
        'ApiRefNo' => $formData['ApiRefNo'] ?? generateReferenceNumber()
    ];
    
    return $mappedData;
}








/**
 * Save user data to CSE API.
 * Returns ['accountId' => int|null, 'error' => string] so caller can show CSE error to user.
 */
function saveUserData($token, $userData) {
    $url = CSE_API_BASE_URL . '/api/User/SaveUser';

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
    liveLog('SaveUser raw response: ' . (strlen($result) > 500 ? substr($result, 0, 500) . '...' : $result));

    $result = trim($result);
    $response = $result ? json_decode($result, true) : null;

    // Extract error message from CSE response (various common shapes)
    $cseError = '';
    if (is_array($response)) {
        $cseError = $response['Message'] ?? $response['message'] ?? $response['error_description'] ?? $response['error'] ?? '';
        if (is_array($cseError)) $cseError = json_encode($cseError);
    }
    if ($cseError === '' && $result && $httpCode >= 400) $cseError = $result;

    if ($httpCode === 200 && $result) {
        // Case 1: API returns raw number as JSON (e.g. 12345)
        if (is_numeric($response)) {
            $accountId = (int)$response;
            liveLog("User saved. AccountID: $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }

        // Case 2: API returns object with AccountID (e.g. {"AccountID": 12345})
        if (is_array($response) && isset($response['AccountID'])) {
            $accountId = (int)$response['AccountID'];
            liveLog("User saved. AccountID: $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }

        // Case 3: API returns plain text number (no JSON)
        if (preg_match('/^\d+$/', $result)) {
            $accountId = (int)$result;
            liveLog("User saved. AccountID (plain): $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }

        liveLog('SaveUser response missing AccountID', 'error');
        return ['accountId' => null, 'error' => $cseError ?: 'Response did not contain AccountID'];
    }

    liveLog("SaveUser failed. HTTP: $httpCode Response: $result", 'error');
    return ['accountId' => null, 'error' => $cseError ?: "HTTP $httpCode. " . (strlen($result) > 200 ? substr($result, 0, 200) . '...' : $result)];
}








/**
 * Save source of funds to CSE API
 * Endpoint: /api/User/SaveSourceFunds
 * 
 * Model: SourceFundsMappers (all fields optional)
 * Items 01-11: String values (probably percentages or amounts)
 * Mot1-Mot4: Additional information fields
 * SaveTable: Optional (default 'F' from old PDF)
 */
function saveSourceFunds($token, $accountId, $formData) {
    liveLog('Preparing source of funds data');
    
    // Get USER_ID from form data
    $userId = $formData['UserID'] ?? generateUserId();
    
    // Create payload with only AccountID and UserID as required
    $payload = [
        'AccountID' => $accountId,
        'UserID' => $userId
    ];
    
    // Add SaveTable if we have it (from old PDF: 'F')
    if (isset($formData['SaveTable'])) {
        $payload['SaveTable'] = $formData['SaveTable'];
    } else {
        $payload['SaveTable'] = 'F'; // Default from old PDF
    }
    
    // ==================== ITEMS 01-11 ====================
    // Based on your form, you have SINGLE dropdown selection
    // Values 1-11 map to Item01-Item11
    
    $sourceFundValue = $formData['SourseOfFund'] ?? '';
    
    if (!empty($sourceFundValue) && is_numeric($sourceFundValue)) {
        // Map dropdown value (1-11) to Item01-Item11
        $itemNumber = str_pad($sourceFundValue, 2, '0', STR_PAD_LEFT);
        $itemKey = 'Item' . $itemNumber;
        
        // Set selected item to indicate source
        // Value could be 'Y', '100%', or something else
        // Need business logic: What value should we send?
        $payload[$itemKey] = 'Y'; // 'Y' for Yes this is the source
        
        liveLog("Source of funds: $itemKey selected");
    }
    
    // ==================== MOT FIELDS ====================
    // Add Mot fields if present in form data
    $motFields = ['Mot1', 'Mot2', 'Mot3', 'Mot4'];
    foreach ($motFields as $motField) {
        if (isset($formData[$motField]) && !empty($formData[$motField])) {
            $payload[$motField] = $formData[$motField];
        }
    }
    
    // Log payload for debugging
    liveLog('Source funds payload: ' . json_encode($payload));
    
    // Call API
    $url = CSE_API_BASE_URL . '/api/User/SaveSourceFunds';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    liveLog("SaveSourceFunds Response HTTP: $httpCode");
    
    // Check response - since all fields are optional, any 200 response is success
    if ($httpCode === 200) {
        liveLog('Source of funds saved successfully');
        return true;
    }
    
    // Log error details
    if ($result) {
        $errorResponse = json_decode($result, true);
        liveLog("SaveSourceFunds error: " . json_encode($errorResponse), 'error');
    } else {
        liveLog("SaveSourceFunds failed with HTTP $httpCode", 'error');
    }
    
    return false;
}











/**
 * Upload images one by one to CSE API (sequential – do NOT send in parallel).
 * Endpoint: /api/ImageUpload/UploadImageOnebyOne
 * CSE requires: first step → SaveUser (get AccountID); second step → send documents one by one.
 *
 * @param string $token Authentication token (valid 15 min)
 * @param int $accountId Account ID from SaveUser response
 * @param array $formData Form data (for UserID)
 * @return bool True if all present files uploaded successfully
 */
function uploadImages($token, $accountId, $formData) {
    $userId = $formData['UserID'] ?? generateUserId();

    // Order must be sequential: one by one (NIC Front, NIC Back, etc.). No parallel.
    $uploadOrder = [
        'selfie_upload'   => 'Selfie',
        'nic_front_upload' => 'NIC Front',
        'nic_back_upload'  => 'NIC Back',
        'passport_upload'  => 'Passport'
    ];

    $allSuccess = true;
    foreach ($uploadOrder as $fileKey => $imageTypeLabel) {
        if (empty($_FILES[$fileKey]['tmp_name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            continue;
        }

        liveLog("Uploading document: $imageTypeLabel (one by one, sequential)");

        $url = CSE_API_BASE_URL . '/api/ImageUpload/UploadImageOnebyOne';
        $filePath = $_FILES[$fileKey]['tmp_name'];

        // If Swagger uses different param names (e.g. File, DocumentType), adjust here
        $postFields = [
            'AccountID'  => $accountId,
            'UserID'     => $userId,
            'ImageType'  => $imageTypeLabel,
            'file'       => new CURLFile($filePath, $_FILES[$fileKey]['type'] ?? 'image/jpeg', $_FILES[$fileKey]['name'])
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_TIMEOUT        => 60
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            liveLog("Upload failed for $imageTypeLabel: HTTP $httpCode - $result", 'error');
            $allSuccess = false;
        } else {
            liveLog("Uploaded: $imageTypeLabel");
        }
    }

    return $allSuccess;
}

// ==================== VALIDATION (doc: Null? Y = required) ====================
/**
 * Validate that all required (Null? Y) fields have a value before SaveUser.
 * Doc: Null? N = nullable (optional), Null? Y = not nullable (required).
 * PEP_Q1–Q4 only required when IsPEP is Y (they are hidden when IsPEP is N).
 */
function validateRequiredSaveUserFields($formData) {
    $required = [
        'Title', 'Initials', 'Surname', 'NameDenoInitials', 'MobileNo', 'Email',
        'IdentificationProof', 'DateOfBirthday', 'Gender', 'BrokerFirm',
        'ResAddressLine01', 'ResAddressTown', 'ResAddressDistrict', 'Country',
        'BankAccountNo', 'BankCode', 'BankBranch', 'BankAccountType',
        'EmployeStatus', 'ExpValueInvestment', 'IsPEP', 'LitigationStatus',
        'CountryOfResidency', 'Nationality', 'ClientType', 'Residency'
    ];
    $idProof = trim($formData['IdentificationProof'] ?? '');
    if ($idProof === 'P') {
        $required[] = 'PassportNo';
    } else {
        $required[] = 'NicNo';
    }
    // When IsPEP is Y, PEP Q1–4 are required; when N they are defaulted in prepareUserData
    $isPEP = strtoupper(trim($formData['IsPEP'] ?? 'N'));
    if ($isPEP === 'Y') {
        $required[] = 'PEP_Q1';
        $required[] = 'PEP_Q2';
        $required[] = 'PEP_Q3';
        $required[] = 'PEP_Q4';
    }
    $missing = [];
    foreach ($required as $key) {
        $val = isset($formData[$key]) ? trim((string)$formData[$key]) : '';
        if ($val === '') {
            $missing[] = $key;
        }
    }
    if (!empty($missing)) {
        throw new Exception('Required fields missing (doc Null? Y): ' . implode(', ', $missing));
    }
}

// ==================== HELPERS (per API doc) ====================
function liveLog($message, $level = 'info') {
    $logFile = defined('CSE_LOG_FILE') && CSE_LOG_FILE ? CSE_LOG_FILE : (CSE_STORAGE_PATH . '/api.log');
    if (!$logFile) return;
    $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
    if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0750, true);
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/** Dates per doc: yyyy/mm/dd */
function formatDateForAPI($date) {
    if (empty($date) || trim($date) === '') return '';
    $t = strtotime($date);
    return $t ? date('Y/m/d', $t) : '';
}

function generateUserId() {
    return (isset($_SERVER['REMOTE_ADDR']) ? str_replace('.', '', $_SERVER['REMOTE_ADDR']) : 'web') . '_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 6);
}

function generateReferenceNumber() {
    return 'REF' . date('YmdHis') . substr(str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT), -4);
}