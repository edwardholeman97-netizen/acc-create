<?php
/**
 * CSE API functions – shared by api.php and admin resubmit.
 * Requires config.php to be loaded.
 */
if (!defined('CSE_API_BASE_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}

function cse_getAuthToken() {
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
            cse_liveLog('Auth token received');
            return $response['access_token'];
        }
    }
    cse_liveLog("Auth failed. HTTP: $httpCode", 'error');
    return null;
}

function cse_generateSignDataForApi($formData) {
    $userId = $formData['UserID'] ?? $formData['Email'] ?? '';
    $displayName = $formData['NameDenoInitials'] ?? '';
    $email = $formData['Email'] ?? '';
    $mobile = $formData['MobileNo'] ?? '';
    return json_encode([
        'DEVICE' => ['MAC' => 'E1A683B05BBF494F', 'OS' => 'ANDROID', 'VERSION' => '31', 'MODEL' => 'SM-M315F', 'LOCATION' => '0,0'],
        'AUTHENTICATOR' => [
            'ATTESTATIONID' => 'A', 'SECURESTORE' => true, 'AUTHENTICATORTYPE' => 'MCA',
            'USEROBJECT' => [
                'ACCOUNTS' => [
                    'ACOPENING' => ['ISREGISTERED' => true, 'USERNAME' => $userId],
                    'ECONNECT' => ['CDSNO' => '', 'ISREGISTERED' => true, 'USERNAME' => $userId],
                    'IPO' => ['ISREGISTERED' => false, 'USERNAME' => ''],
                    'MYCSE' => ['ISREGISTERED' => true, 'USERNAME' => $userId]
                ],
                'DISPLAYNAME' => $displayName, 'EMAIL' => $email,
                'MOBILENO' => ['COUNTRYCODE' => '+94', 'MOBILENO' => $mobile],
                'USERNAME' => $userId, 'USERCONSENTACQUIRED' => 'TRUE'
            ]
        ]
    ], JSON_UNESCAPED_SLASHES);
}

function cse_prepareUserData($formData) {
    cse_liveLog('Mapping form data to CSE fields');
    $idProofRaw = $formData['IdentificationProof'] ?? 'P';
    $idProof = (strtoupper(substr(trim($idProofRaw), 0, 1)) === 'N') ? 'N' : 'P';
    // When Passport: Oracle NIC_NO is NOT NULL, send placeholder (empty string becomes NULL)
    $nicNo = ($idProof === 'P') ? 'NA' : ($formData['NicNo'] ?? '');
    $accountId = isset($formData['AccountID']) ? (int)$formData['AccountID'] : 0;
    $status = $formData['Status'] ?? '1';

    $mappedData = [
        'AccountID' => $accountId,
        'UserID' => $formData['UserID'] ?? cse_generateUserId(),
        'Title' => $formData['Title'] ?? '',
        'Initials' => substr($formData['Initials'] ?? '', 0, 15),
        'Surname' => substr($formData['Surname'] ?? '', 0, 50),
        'NameDenoInitials' => substr($formData['NameDenoInitials'] ?? '', 0, 160),
        'MobileNo' => substr($formData['MobileNo'] ?? '', 0, 16),
        'TelphoneNo' => substr($formData['TelphoneNo'] ?? '', 0, 16),
        'Email' => substr($formData['Email'] ?? '', 0, 100),
        'IdentificationProof' => $idProof,
        'NicNo' => $nicNo,
        'PassportNo' => $formData['PassportNo'] ?? '',
        'PassportExpDate' => cse_formatDateForAPI($formData['PassportExpDate'] ?? ''),
        'DateOfBirthday' => cse_formatDateForAPI($formData['DateOfBirthday'] ?? ''),
        'Gender' => $formData['Gender'] ?? '',
        'BrokerFirm' => substr($formData['BrokerFirm'] ?? '', 0, 3),
        'ExitCDSAccount' => $formData['ExitCDSAccount'] ?? 'N',
        'CDSAccountNo' => substr($formData['CDSAccountNo'] ?? '', 0, 20),
        'TinNo' => substr($formData['TinNo'] ?? '', 0, 20),
        'ResAddressStatus' => $formData['ResAddressStatus'] ?? 'Y',
        'ResAddressStatusDesc' => $formData['ResAddressStatusDesc'] ?? '',
        'ResAddressLine01' => substr($formData['ResAddressLine01'] ?? '', 0, 30),
        'ResAddressLine02' => substr($formData['ResAddressLine02'] ?? '', 0, 30),
        'ResAddressLine03' => substr($formData['ResAddressLine03'] ?? '', 0, 15),
        'ResAddressTown' => substr($formData['ResAddressTown'] ?? '', 0, 15),
        'ResAddressDistrict' => $formData['ResAddressDistrict'] ?? '',
        'Country' => substr($formData['Country'] ?? '', 0, 4),
        'CorrAddressStatus' => $formData['CorrAddressStatus'] ?? 'Y',
        'CorrAddressLine01' => substr($formData['CorrAddressLine01'] ?? '', 0, 30),
        'CorrAddressLine02' => substr($formData['CorrAddressLine02'] ?? '', 0, 30),
        'CorrAddressLine03' => substr($formData['CorrAddressLine03'] ?? '', 0, 15),
        'CorrAddressTown' => substr($formData['CorrAddressTown'] ?? '', 0, 15),
        'CorrAddressDistrict' => $formData['CorrAddressDistrict'] ?? '',
        'BankAccountNo' => substr($formData['BankAccountNo'] ?? '', 0, 12),
        'BankCode' => substr($formData['BankCode'] ?? '', 0, 4),
        'BankBranch' => substr($formData['BankBranch'] ?? '', 0, 4),
        'BankAccountType' => $formData['BankAccountType'] ?? 'I',
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
        'ExpValueInvestment' => $formData['ExpValueInvestment'] ?? '1',
        'SourseOfFund' => $formData['SourseOfFund'] ?? '',
        'UsaPersonStatus' => $formData['UsaPersonStatus'] ?? 'N',
        'UsaTaxIdentificationNo' => $formData['UsaTaxIdentificationNo'] ?? $formData['UsaTaxIdentifierNo'] ?? '',
        'FactaDeclaration' => $formData['FactaDeclaration'] ?? 'N',
        'DualCitizenship' => $formData['DualCitizenship'] ?? 'N',
        'DualCitizenCountry' => $formData['DualCitizenCountry'] ?? '',
        'DualCitizenPassport' => $formData['DualCitizenPassport'] ?? '',
        'IsPEP' => $formData['IsPEP'] ?? 'N',
        'PepQ1' => $formData['PEP_Q1'] ?? 'N',
        'PepQ1Details' => $formData['PEP_Q1_Details'] ?? '',
        'PepQ2' => $formData['PEP_Q2'] ?? 'N',
        'PepQ2Details' => $formData['PEP_Q2_Details'] ?? '',
        'PepQ3' => $formData['PEP_Q3'] ?? 'N',
        'PepQ3Details' => $formData['PEP_Q3_Details'] ?? '',
        'PepQ4' => $formData['PEP_Q4'] ?? 'N',
        'PepQ4Detailas' => $formData['PEP_Q4_Details'] ?? '',
        'LatigationStatus' => $formData['LitigationStatus'] ?? 'N',
        'LatigationDetails' => $formData['LitigationDetails'] ?? '',
        'Status' => $status,
        'EnterUser' => $formData['EnterUser'] ?? 'SYSTEM',
        'SaveTable' => $formData['SaveTable'] ?? 'U',
        'SignData' => !empty($formData['SignData']) ? $formData['SignData'] : cse_generateSignDataForApi($formData),
        'StrockBrokerFirmName' => $formData['BrokerFirm'] ?? '',
        'CountryOfResidency' => $formData['CountryOfResidency'] ?? '',
        'Nationality' => $formData['Nationality'] ?? '',
        'ClientType' => $formData['ClientType'] ?? 'FI',
        'Residency' => $formData['Residency'] ?? 'R',
        'IsLKPassport' => $formData['IsLKPassport'] ?? 'N',
        'InvestorId' => $formData['InvestorId'] ?? '',
        'InvestmentOb' => $formData['InvestmentOb'] ?? '',
        'InvestmentStrategy' => $formData['InvestmentStrategy'] ?? '',
        'EnterDate' => cse_formatDateForAPI($formData['EnterDate'] ?? date('Y-m-d')),
        'ApiUser' => $formData['ApiUser'] ?? 'DIALOG',
        'ApiRefNo' => $formData['ApiRefNo'] ?? cse_generateReferenceNumber()
    ];
    return $mappedData;
}

function cse_saveUserData($token, $userData) {
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
    cse_liveLog("SaveUser Response HTTP: $httpCode");
    $result = trim($result);
    $response = $result ? json_decode($result, true) : null;
    $isErrorBody = $result && (stripos($result, 'Exception') !== false || stripos($result, 'ORA-') !== false);
    if ($httpCode === 200 && $isErrorBody) {
        cse_liveLog('SaveUser returned 200 but body is error', 'error');
        return ['accountId' => null, 'error' => strlen($result) > 500 ? substr($result, 0, 500) . '...' : $result];
    }
    $cseError = '';
    if (is_array($response)) {
        $cseError = $response['Message'] ?? $response['message'] ?? $response['error_description'] ?? $response['error'] ?? '';
        if (is_array($cseError)) $cseError = json_encode($cseError);
    }
    if ($cseError === '' && $result && $httpCode >= 400) $cseError = $result;
    if ($httpCode === 200 && $result && !$isErrorBody) {
        if (is_numeric($response)) {
            $accountId = (int)$response;
            cse_liveLog("User saved. AccountID: $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }
        if (is_string($response) && preg_match('/^\d+$/', trim($response))) {
            $accountId = (int)$response;
            cse_liveLog("User saved. AccountID (string): $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }
        if (is_array($response) && isset($response['AccountID'])) {
            $accountId = (int)$response['AccountID'];
            cse_liveLog("User saved. AccountID: $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }
        if (preg_match('/^\d+$/', $result)) {
            $accountId = (int)$result;
            cse_liveLog("User saved. AccountID (plain): $accountId");
            return ['accountId' => $accountId, 'error' => ''];
        }
        return ['accountId' => null, 'error' => $cseError ?: 'Response did not contain AccountID'];
    }
    cse_liveLog("SaveUser failed. HTTP: $httpCode", 'error');
    return ['accountId' => null, 'error' => $cseError ?: "HTTP $httpCode. " . (strlen($result) > 200 ? substr($result, 0, 200) . '...' : $result)];
}

function cse_saveSourceFunds($token, $accountId, $formData) {
    cse_liveLog('Preparing source of funds data');
    $userId = $formData['UserID'] ?? cse_generateUserId();
    $payload = [
        'AccountID' => $accountId,
        'UserID' => $userId,
        'SaveTable' => $formData['SaveTable'] ?? 'F'
    ];
    $sourceFundValue = $formData['SourseOfFund'] ?? '';
    if (!empty($sourceFundValue) && is_numeric($sourceFundValue)) {
        $itemNumber = str_pad($sourceFundValue, 2, '0', STR_PAD_LEFT);
        $payload['Item' . $itemNumber] = 'Y';
    }
    foreach (['Mot1', 'Mot2', 'Mot3', 'Mot4'] as $motField) {
        if (!empty($formData[$motField])) $payload[$motField] = $formData[$motField];
    }
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
    cse_liveLog("SaveSourceFunds Response HTTP: $httpCode");
    return $httpCode === 200;
}

function cse_validateRequiredSaveUserFields($formData) {
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
    $isPEP = strtoupper(trim($formData['IsPEP'] ?? 'N'));
    if ($isPEP === 'Y') {
        $required[] = 'PEP_Q1'; $required[] = 'PEP_Q2'; $required[] = 'PEP_Q3'; $required[] = 'PEP_Q4';
    }
    $missing = [];
    foreach ($required as $key) {
        $val = isset($formData[$key]) ? trim((string)$formData[$key]) : '';
        if ($val === '') $missing[] = $key;
    }
    if (!empty($missing)) {
        throw new Exception('Required fields missing: ' . implode(', ', $missing));
    }
}

function cse_liveLog($message, $level = 'info') {
    $logFile = defined('CSE_LOG_FILE') && CSE_LOG_FILE ? CSE_LOG_FILE : (CSE_STORAGE_PATH . '/api.log');
    if (!$logFile) return;
    $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
    if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0750, true);
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function cse_formatDateForAPI($date) {
    if (empty($date) || trim($date) === '') return '';
    $t = strtotime($date);
    return $t ? date('Y/m/d', $t) : '';
}

function cse_generateUserId() {
    return (isset($_SERVER['REMOTE_ADDR']) ? str_replace('.', '', $_SERVER['REMOTE_ADDR']) : 'web') . '_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 6);
}

function cse_generateReferenceNumber() {
    return substr('REF' . date('YmdHis') . (string)mt_rand(0, 99), 0, 15);
}

/**
 * Call CSE OtherServices endpoint (GetTitle, GetBroker, etc.) for dropdown data.
 * @param string $path e.g. '/api/OtherServices/GetTitle'
 * @return array ['success' => bool, 'data' => array of rows]
 */
function cse_callOtherService($path) {
    $token = cse_getAuthToken();
    if (!$token) return ['success' => false, 'data' => []];
    $url = CSE_API_BASE_URL . $path;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200 || !$result) return ['success' => false, 'data' => []];
    $decoded = json_decode($result, true);
    $data = [];
    if (is_array($decoded)) {
        if (isset($decoded[0]) && is_array($decoded[0])) $data = $decoded;
        elseif (isset($decoded['Data'])) $data = $decoded['Data'];
        elseif (isset($decoded['data'])) $data = $decoded['data'];
    }
    return ['success' => true, 'data' => is_array($data) ? $data : []];
}

/**
 * Get titles for dropdown (value => label). Fallback to common titles if API fails.
 * @return array [value => label, ...]
 */
function cse_getTitlesForDropdown() {
    $res = cse_callOtherService('/api/OtherServices/GetTitle');
    $opts = [];
    if ($res['success'] && !empty($res['data'])) {
        foreach ($res['data'] as $row) {
            $v = $row['TITLE_ID'] ?? $row['TITLE_CODE'] ?? $row['TitleCode'] ?? $row['title_code'] ?? '';
            $l = $row['TITLE_NAME'] ?? $row['TitleName'] ?? $row['title_name'] ?? $v;
            if ((string)$v !== '') $opts[(string)$v] = (string)$l;
        }
    }
    if (empty($opts)) {
        $opts = ['Mr' => 'Mr', 'Mrs' => 'Mrs', 'Miss' => 'Miss', 'Dr' => 'Dr', 'Prof' => 'Prof'];
    }
    return $opts;
}

/**
 * Get brokers for dropdown (value => label), filtered by ALLOWED_BROKER_FILTER.
 * Matches resource.php getBrokers filter logic.
 * @return array [value => label, ...]
 */
function cse_getBrokersForDropdown() {
    $res = cse_callOtherService('/api/OtherServices/GetBroker');
    $opts = [];
    if ($res['success'] && !empty($res['data'])) {
        $items = $res['data'];
        $filter = function_exists('env') ? env('ALLOWED_BROKER_FILTER', 'S C SECURITIES') : 'S C SECURITIES';
        if ($filter !== '') {
            $filterNorm = preg_replace('/\s+/', ' ', trim($filter));
            $items = array_values(array_filter($items, function ($b) use ($filterNorm) {
                $name = $b['BROKER_FULL_NAME'] ?? $b['BrokerFullName'] ?? '';
                return stripos(preg_replace('/\s+/', ' ', trim((string)$name)), $filterNorm) !== false;
            }));
        }
        foreach ($items as $row) {
            $v = $row['BROKER_ID'] ?? $row['BrokerId'] ?? $row['broker_id'] ?? '';
            $l = $row['BROKER_FULL_NAME'] ?? $row['BrokerFullName'] ?? $row['broker_full_name'] ?? $v;
            if ((string)$v !== '') $opts[(string)$v] = (string)$l;
        }
    }
    return $opts;
}

/**
 * Upload images to CSE API per doc: ACCOUNT_ID, USER_ID, IMAGE_TYPE, IMAGE_BASE64_TYPE (base64), IMAGE_CT (mime).
 * IMAGE_TYPE: 1=Selfie, 2=NIC Front, 3=NIC Back, 4=Passport. Max 2MB, image/png or image/jpeg only.
 * @param string $token Auth token
 * @param int $accountId Account ID
 * @param array $formData Form data (UserID, Email)
 * @param array $imagePaths Map of label=>relative path (e.g. selfie=>uploads/501228/selfie.png). Paths relative to CSE_STORAGE_PATH.
 * @return bool True if all uploaded successfully
 */
function cse_uploadImages($token, $accountId, array $formData, array $imagePaths): bool {
    $userId = $formData['UserID'] ?? $formData['Email'] ?? cse_generateUserId();
    $baseDir = rtrim(CSE_STORAGE_PATH, '/');
    $uploadOrder = ['selfie' => 1, 'nic_front' => 2, 'nic_back' => 3, 'passport' => 4];
    $allSuccess = true;
    $first = true;
    foreach ($uploadOrder as $label => $imageType) {
        $path = $imagePaths[$label] ?? null;
        if (!$path) continue;
        $fullPath = $baseDir . '/' . ltrim($path, '/');
        if (!is_file($fullPath)) continue;
        if (filesize($fullPath) > 2 * 1024 * 1024) {
            cse_liveLog("Image $label exceeds 2MB, skipping", 'error');
            $allSuccess = false;
            continue;
        }
        if (!$first) sleep(2);
        $first = false;
        $imageBase64 = base64_encode(file_get_contents($fullPath));
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = in_array($ext, ['png', 'gif'], true) ? 'image/' . $ext : 'image/jpeg';
        if ($ext === 'gif') $mime = 'image/jpeg'; // Doc: only image/png, image/jpeg
        $payload = [
            'ACCOUNT_ID' => (int)$accountId,
            'USER_ID' => $userId,
            'IMAGE_TYPE' => (string)$imageType,
            'IMAGE_BASE64_TYPE' => $imageBase64,
            'IMAGE_CT' => $mime
        ];
        $url = CSE_API_BASE_URL . '/api/ImageUpload/UploadImageOnebyOne';
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
            CURLOPT_TIMEOUT => 60
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) {
            cse_liveLog("Upload failed for $label: HTTP $httpCode - $result", 'error');
            $allSuccess = false;
        } else {
            cse_liveLog("Uploaded: $label");
        }
    }
    return $allSuccess;
}

/**
 * Resubmit edited form data to CSE API. For admin edit flow.
 * Optionally re-uploads images to CSE when imagePaths provided.
 * @param array $formData Updated form data
 * @param string $accountId Existing account ID for resubmit
 * @param array $imagePaths Optional. Map of label=>path (e.g. selfie=>uploads/501228/selfie.png) to upload to CSE
 * @return array ['success' => bool, 'message' => string, 'accountId' => string|null]
 */
function cse_resubmitToApi(array $formData, string $accountId, array $imagePaths = []): array {
    $formData['AccountID'] = (int)$accountId;
    $formData['Status'] = '4'; // 4 = resubmit per API doc
    if (empty($formData['UserID'])) {
        $formData['UserID'] = $formData['Email'] ?? cse_generateUserId();
    }
    try {
        cse_validateRequiredSaveUserFields($formData);
        $token = cse_getAuthToken();
        if (!$token) return ['success' => false, 'message' => 'Authentication failed', 'accountId' => null];
        $userData = cse_prepareUserData($formData);
        $saveResult = cse_saveUserData($token, $userData);
        $returnedAccountId = $saveResult['accountId'] ?? null;
        if (!$returnedAccountId) {
            return ['success' => false, 'message' => $saveResult['error'] ?? 'Failed to save to CSE', 'accountId' => null];
        }
        cse_saveSourceFunds($token, $returnedAccountId, $formData);
        if (!empty($imagePaths)) {
            cse_uploadImages($token, (int)$returnedAccountId, $formData, $imagePaths);
        }
        return ['success' => true, 'message' => 'Successfully resubmitted to CSE', 'accountId' => (string)$returnedAccountId];
    } catch (Throwable $e) {
        cse_liveLog('Resubmit error: ' . $e->getMessage(), 'error');
        return ['success' => false, 'message' => $e->getMessage(), 'accountId' => null];
    }
}
