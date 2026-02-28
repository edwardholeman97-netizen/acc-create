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
    
    // ==================== HANDLE JSON AND MULTIPART ====================
    $formData = [];
    $hasFiles = false;
    $step = null;  // 'submit' = form only (get accountId), 'upload' = images only (need accountId)
    
    if ($_SERVER['CONTENT_TYPE'] && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
        liveLog('Request is multipart/form-data');
        $hasFiles = !empty($_FILES);
        foreach ($_POST as $key => $value) {
            $formData[$key] = $value;
        }
        $step = $formData['step'] ?? null;
        if (!isset($formData['UserID'])) {
            $formData['UserID'] = $formData['Email'] ?? generateUserId();
        }
    } else {
        liveLog('Request is JSON');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['formData'])) {
            throw new Exception('Invalid or empty JSON data');
        }
        $formData = $input['formData'];
        $step = $input['step'] ?? null;
    }
    
    if (empty($formData)) {
        throw new Exception('No form data received');
    }
    liveLog('Form data processed. Step: ' . ($step ?: 'full'));

    // ==================== STEP: UPLOAD IMAGES ONLY (after account created) ====================
    if ($step === 'upload') {
        $accountId = $formData['accountId'] ?? $formData['account_id'] ?? null;
        if (!$accountId) {
            throw new Exception('Account ID required for image upload');
        }
        $accountId = (string)preg_replace('/[^0-9]/', '', $accountId);
        if ($accountId === '') {
            throw new Exception('Invalid account ID');
        }
        
        liveLog('Step UPLOAD: Uploading images with AccountID=' . $accountId);
        if (empty($formData['UserID'])) {
            $formData['UserID'] = $formData['Email'] ?? generateUserId();
        }
        $token = getAuthToken();
        if (!$token) throw new Exception('Authentication failed');

        $imageUploadSuccess = uploadImages($token, (int)$accountId, $formData);
        liveLog('Image upload completed: ' . ($imageUploadSuccess ? 'Success' : 'Partial/Failed'));

        $imagePaths = saveImagesToStorage($accountId);
        updateSubmissionImages($accountId, $imagePaths);

        $response['success'] = true;
        $response['message'] = 'Images uploaded';
        $response['accountId'] = $accountId;
        $response['imagesUploaded'] = $imageUploadSuccess;
        liveLog('Upload step completed');
        echo json_encode($response);
        exit;
    }

    // ==================== STEP: SUBMIT FORM ONLY (create account, no images) ====================
    validateRequiredSaveUserFields($formData);
    liveLog('Step 1: Authentication');
    $token = getAuthToken();
    if (!$token) throw new Exception('Authentication failed');

    liveLog('Step 2: Preparing user data');
    $userData = prepareUserData($formData);

    liveLog('Step 3: Saving user data to CSE');
    $saveResult = saveUserData($token, $userData);
    $accountId = $saveResult['accountId'] ?? null;
    if (!$accountId) {
        $msg = trim($saveResult['error'] ?? '');
        throw new Exception($msg ?: 'Failed to save user data');
    }

    liveLog('Step 4: Saving source of funds');
    $sourceFundsSuccess = saveSourceFunds($token, $accountId, $formData);

    liveLog('Step 5: Saving submission to DB');
    saveSubmissionToDB((string)$accountId, $formData, []);

    $response['success'] = true;
    $response['message'] = 'Account created.';
    $response['accountId'] = $accountId;
    $response['sourceFundsSaved'] = $sourceFundsSuccess;
    liveLog('Submit step completed. AccountID=' . $accountId);

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
 * Generate SignData JSON for CSE API (same structure as thimira-v2.php).
 * Uses UserID, NameDenoInitials, Email, MobileNo from form.
 */
function generateSignDataForApi($formData) {
    $userId = $formData['UserID'] ?? $formData['Email'] ?? '';
    $displayName = $formData['NameDenoInitials'] ?? '';
    $email = $formData['Email'] ?? '';
    $mobile = $formData['MobileNo'] ?? '';
    return json_encode([
        'DEVICE' => [
            'MAC' => 'E1A683B05BBF494F',
            'OS' => 'ANDROID',
            'VERSION' => '31',
            'MODEL' => 'SM-M315F',
            'LOCATION' => '0,0'
        ],
        'AUTHENTICATOR' => [
            'ATTESTATIONID' => 'A',
            'SECURESTORE' => true,
            'AUTHENTICATORTYPE' => 'MCA',
            'USEROBJECT' => [
                'ACCOUNTS' => [
                    'ACOPENING' => ['ISREGISTERED' => true, 'USERNAME' => $userId],
                    'ECONNECT' => ['CDSNO' => '', 'ISREGISTERED' => true, 'USERNAME' => $userId],
                    'IPO' => ['ISREGISTERED' => false, 'USERNAME' => ''],
                    'MYCSE' => ['ISREGISTERED' => true, 'USERNAME' => $userId]
                ],
                'DISPLAYNAME' => $displayName,
                'EMAIL' => $email,
                'MOBILENO' => ['COUNTRYCODE' => '+94', 'MOBILENO' => $mobile],
                'USERNAME' => $userId,
                'USERCONSENTACQUIRED' => 'TRUE'
            ]
        ]
    ], JSON_UNESCAPED_SLASHES);
}

/**
 * Prepare user data for CSE API (SaveUser).
 * Field list, types, nullability: see API doc spreadsheet (link in CONFIG above).
 * Dates: yyyy/mm/dd per doc. STATUS: 1=submit, 4=resubmit. GetTitle/GetBroker/GetDistrict/GetCountry/GetBank/GetBankBranch/GetInvestAdvisors for dropdowns.
 */
function prepareUserData($formData) {
    liveLog('Mapping form data to CSE fields');
    // CSE API expects 1 char: N = NIC, P = Passport (column max length 1)
    $idProofRaw = $formData['IdentificationProof'] ?? 'P';
    $idProof = (strtoupper(substr(trim($idProofRaw), 0, 1)) === 'N') ? 'N' : 'P';
    // When Passport: Oracle NIC_NO is NOT NULL, send placeholder (empty string becomes NULL)
    $nicNo = ($idProof === 'P') ? 'NA' : ($formData['NicNo'] ?? '');
    
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
        'UsaTaxIdentificationNo' => $formData['UsaTaxIdentificationNo'] ?? $formData['UsaTaxIdentifierNo'] ?? '',
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
        'SignData' => !empty($formData['SignData']) ? $formData['SignData'] : generateSignDataForApi($formData),
        
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

    // CSE sometimes returns HTTP 200 with error text in body (e.g. Oracle exception)
    $isErrorBody = $result && (
        stripos($result, 'Exception') !== false ||
        stripos($result, 'ORA-') !== false
    );
    if ($httpCode === 200 && $isErrorBody) {
        liveLog('SaveUser returned 200 but body is error: ' . substr($result, 0, 300), 'error');
        return ['accountId' => null, 'error' => strlen($result) > 500 ? substr($result, 0, 500) . '...' : $result];
    }

    // Extract error message from CSE response (various common shapes)
    $cseError = '';
    if (is_array($response)) {
        $cseError = $response['Message'] ?? $response['message'] ?? $response['error_description'] ?? $response['error'] ?? '';
        if (is_array($cseError)) $cseError = json_encode($cseError);
    }
    if ($cseError === '' && $result && $httpCode >= 400) $cseError = $result;

    if ($httpCode === 200 && $result && !$isErrorBody) {
        // Case 1: API returns number (JSON number or JSON string of digits)
        if (is_numeric($response)) {
            $accountId = (int)$response;
            liveLog("User saved. AccountID: $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }
        // Case 1b: API returns quoted string with digits (e.g. "501149")
        if (is_string($response) && preg_match('/^\d+$/', trim($response))) {
            $accountId = (int)$response;
            liveLog("User saved. AccountID (string): $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }

        // Case 2: API returns object with AccountID (e.g. {"AccountID": 12345})
        if (is_array($response) && isset($response['AccountID'])) {
            $accountId = (int)$response['AccountID'];
            liveLog("User saved. AccountID: $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }

        // Case 3: API returns plain text number (no JSON) - raw body is just digits
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
 * Doc payload: ACCOUNT_ID, USER_ID, IMAGE_TYPE, IMAGE_BASE64_TYPE (base64), IMAGE_CT (mime). Max 2MB.
 *
 * @param string $token Authentication token (valid 15 min)
 * @param int $accountId Account ID from SaveUser response
 * @param array $formData Form data (for UserID)
 * @return bool True if all present files uploaded successfully
 */
function uploadImages($token, $accountId, $formData) {
    $userId = $formData['UserID'] ?? generateUserId();
    $uploadOrder = [
        'selfie_upload'   => 1,
        'nic_front_upload' => 2,
        'nic_back_upload'  => 3,
        'passport_upload'  => 4
    ];

    $allSuccess = true;
    $first = true;
    foreach ($uploadOrder as $fileKey => $imageType) {
        if (empty($_FILES[$fileKey]['tmp_name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            continue;
        }
        // Wait between uploads (one by one) – CSE may require sequential processing
        if (!$first) {
            sleep(2);
        }
        $first = false;

        $label = ['1' => 'Selfie', '2' => 'NIC Front', '3' => 'NIC Back', '4' => 'Passport'][(string)$imageType] ?? $imageType;
        liveLog("Uploading document: $label (one by one, sequential)");

        $filePath = $_FILES[$fileKey]['tmp_name'];
        if (filesize($filePath) > 2 * 1024 * 1024) {
            liveLog("Image $label exceeds 2MB, skipping", 'error');
            $allSuccess = false;
            continue;
        }
        $url = CSE_API_BASE_URL . '/api/ImageUpload/UploadImageOnebyOne';
        $imageBase64 = base64_encode(file_get_contents($filePath));
        $mime = $_FILES[$fileKey]['type'] ?? '';
        if (!preg_match('#^image/(jpeg|jpg|png|gif)#i', $mime)) {
            $mime = 'image/jpeg';
        }

        $payload = [
            'ACCOUNT_ID'        => (int)$accountId,
            'USER_ID'           => $userId,
            'IMAGE_TYPE'        => (string)$imageType,
            'IMAGE_BASE64_TYPE' => $imageBase64,
            'IMAGE_CT'          => $mime
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 60
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            liveLog("Upload failed for $label: HTTP $httpCode - $result", 'error');
            $allSuccess = false;
        } else {
            liveLog("Uploaded: $label");
        }
    }

    return $allSuccess;
}

/**
 * Save uploaded images to server storage: storage/uploads/{account_id}/
 * Returns array of saved paths keyed by type.
 */
function saveImagesToStorage(string $accountId): array {
    $baseDir = rtrim(CSE_STORAGE_PATH, '/') . '/uploads/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $accountId);
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0750, true);
    }
    $paths = [];
    $map = [
        'selfie_upload'   => 'selfie',
        'nic_front_upload' => 'nic_front',
        'nic_back_upload'  => 'nic_back',
        'passport_upload'  => 'passport'
    ];
    foreach ($map as $fileKey => $baseName) {
        if (empty($_FILES[$fileKey]['tmp_name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            continue;
        }
        $ext = pathinfo($_FILES[$fileKey]['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg';
        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png'], true)) {
            $ext = 'jpg';
        }
        $dest = $baseDir . '/' . $baseName . '.' . $ext;
        if (@copy($_FILES[$fileKey]['tmp_name'], $dest)) {
            $paths[$baseName] = 'uploads/' . basename($baseDir) . '/' . basename($dest);
            liveLog("Saved image to: $dest");
        }
    }
    return $paths;
}

/**
 * Update only image_paths for an existing submission.
 */
function updateSubmissionImages(string $accountId, array $imagePaths): void {
    try {
        require_once __DIR__ . '/database/connection.php';
        $pdo = getDb();
        $stmt = $pdo->prepare('UPDATE cds_submissions SET image_paths = ?, updated_at = NOW() WHERE account_id = ?');
        $stmt->execute([json_encode($imagePaths, JSON_UNESCAPED_UNICODE), $accountId]);
        if ($stmt->rowCount() > 0) {
            liveLog('Updated image paths in DB: account_id=' . $accountId);
        }
    } catch (Throwable $e) {
        liveLog('DB image update failed: ' . $e->getMessage(), 'error');
    }
}

/**
 * Save submission record to database.
 */
function saveSubmissionToDB(string $accountId, array $formData, array $imagePaths = []): void {
    try {
        require_once __DIR__ . '/database/connection.php';
        $pdo = getDb();
        $stmt = $pdo->prepare('INSERT INTO cds_submissions (account_id, form_data, image_paths) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE form_data = VALUES(form_data), image_paths = VALUES(image_paths), updated_at = NOW()');
        $formJson = json_encode($formData, JSON_UNESCAPED_UNICODE);
        $imgJson = empty($imagePaths) ? null : json_encode($imagePaths, JSON_UNESCAPED_UNICODE);
        $stmt->execute([$accountId, $formJson, $imgJson]);
        liveLog('Saved submission to DB: account_id=' . $accountId);
    } catch (Throwable $e) {
        liveLog('DB save failed: ' . $e->getMessage(), 'error');
    }
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
    $idProofRaw = trim($formData['IdentificationProof'] ?? '');
    $isPassport = (strtoupper(substr($idProofRaw, 0, 1)) === 'P');
    if ($isPassport) {
        $required[] = 'PassportNo';
        $required[] = 'PassportExpDate';
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

/** API_REF_NO column max length is 15 */
function generateReferenceNumber() {
    return substr('REF' . date('YmdHis') . (string)mt_rand(0, 99), 0, 15);
}