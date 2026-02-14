<?php
// API credentials
define('API_USERNAME', 'SCTestUser');
define('API_PASSWORD', '2d26tF&M!cqS');
define('API_BASE_URL', 'https://uat-cseapi.cse.lk');

// Helper: get access token
function getAccessToken() {
    $url = API_BASE_URL . '/token';
    $data = http_build_query([
        'username' => API_USERNAME,
        'password' => API_PASSWORD,
        'grant_type' => 'password'
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Cache-Control: no-cache'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => 'Token request failed (HTTP ' . $httpCode . '): ' . $response];
    }

    $json = json_decode($response, true);
    if (isset($json['access_token'])) {
        return ['token' => $json['access_token']];
    } else {
        return ['error' => 'Invalid token response'];
    }
}

// Helper: generate SignData JSON (based on documentation)
function generateSignData($postData) {
    $userId = $postData['USER_ID'] ?? '';
    $displayName = $postData['NAMES_DENO_INITIALS'] ?? '';
    $email = $postData['EMAIL_ADDRESS'] ?? '';
    $mobile = $postData['MOBILE_NO'] ?? '';

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

// Process form submission
$result = null;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Required fields based on Excel (Null? = N) ---
    $required_fields = [
        'USER_ID', 'TITLE', 'INITIALS', 'SURNAME', 'NAMES_DENO_INITIALS',
        'MOBILE_NO', 'EMAIL_ADDRESS', 'NIC_NO', 'DATE_OF_BIRTH', 'GENDER',
        'STOCK_BROKER_FIRM', 'RES_ADDRESS_STATUS', 'RES_ADDRESS_STATUS_DESC',
        'RES_ADDRESS_LINE_1', 'RES_ADDRESS_TOWN', 'RES_ADDRESS_DISTRICT',
        'COUNTRY', 'BANK_ACCOUNT_NO', 'BANK_CODE', 'BANK_BRANCH',
        'BANK_ACCOUNT_TYPE', 'EMPLOYE_STATUS', 'EXP_VALUE_INVESTMENT',
        'IS_PEP', 'PEP_Q1', 'PEP_Q2', 'PEP_Q3', 'PEP_Q4',
        'LITIGATION_STATUS', 'ENTER_USER', 'COUNTRY_OF_RESIDENCY',
        'NATIONALITY', 'RESIDENCY'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $errors[] = "Field '$field' is required.";
        }
    }

    // Optional: basic format checks
    if (isset($_POST['EMAIL_ADDRESS']) && !filter_var($_POST['EMAIL_ADDRESS'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (isset($_POST['MOBILE_NO']) && !preg_match('/^[0-9]{10}$/', $_POST['MOBILE_NO'])) {
        $errors[] = "Mobile number must be 10 digits.";
    }
    if (isset($_POST['DATE_OF_BIRTH']) && !preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $_POST['DATE_OF_BIRTH'])) {
        $errors[] = "Date of Birth must be in yyyy/mm/dd format.";
    }
    // NIC: either 9 digits + V or 12 digits
    if (isset($_POST['NIC_NO']) && !preg_match('/^([0-9]{9}[Vv]|[0-9]{12})$/', $_POST['NIC_NO'])) {
        $errors[] = "NIC must be 9 digits + V or 12 digits.";
    }

    if (empty($errors)) {
        // Get token
        $tokenData = getAccessToken();
        if (isset($tokenData['error'])) {
            $result = ['error' => $tokenData['error']];
        } else {
            $token = $tokenData['token'];

            // Build request data for SaveUser
            $postData = [
                // Fixed / generated
                'AccountID' => 0,
                'ApiRefNo' => substr(md5(uniqid(mt_rand(), true)), 0, 10),
                'IdentificationProof' => 'P',
                'Status' => '1',
                'ClientType' => 'FI',
                'ApiUser' => 'DIALOG',
                'SaveTable' => '',
                'SignData' => generateSignData($_POST),

                // From form
                'UserID' => $_POST['USER_ID'],
                'Title' => $_POST['TITLE'],
                'Initials' => $_POST['INITIALS'],
                'Surname' => $_POST['SURNAME'],
                'NameDenoInitials' => $_POST['NAMES_DENO_INITIALS'],
                'MobileNo' => $_POST['MOBILE_NO'],
                'TelphoneNo' => $_POST['TELEPHONE'] ?? '',
                'Email' => $_POST['EMAIL_ADDRESS'],
                'NicNo' => $_POST['NIC_NO'],
                'PassportNo' => $_POST['PASSPORT_NO'] ?? '',
                'PassportExpDate' => $_POST['PASSPORT_EXP_DATE'] ?? '',
                'DateOfBirthday' => $_POST['DATE_OF_BIRTH'],
                'Gender' => $_POST['GENDER'],
                'BrokerFirm' => $_POST['STOCK_BROKER_FIRM'],
                'ExitCDSAccount' => $_POST['EXIST_CDS_ACCOUNT'] ?? '',
                'CDSAccountNo' => $_POST['CDS_ACCOUNT_NO'] ?? '',
                'TinNo' => $_POST['TIN_NO'] ?? '',
                'ResAddressStatus' => $_POST['RES_ADDRESS_STATUS'],
                'ResAddressStatusDesc' => $_POST['RES_ADDRESS_STATUS_DESC'],
                'ResAddressLine01' => $_POST['RES_ADDRESS_LINE_1'],
                'ResAddressLine02' => $_POST['RES_ADDRESS_LINE_2'] ?? '',
                'ResAddressLine03' => $_POST['RES_ADDRESS_LINE_3'] ?? '',
                'ResAddressTown' => $_POST['RES_ADDRESS_TOWN'],
                'ResAddressDistrict' => $_POST['RES_ADDRESS_DISTRICT'],
                'Country' => $_POST['COUNTRY'],
                'CorrAddressStatus' => $_POST['CORR_ADDRESS_STATUS'] ?? '',
                'CorrAddressLine01' => $_POST['CORR_ADDRESS_LINE_1'] ?? '',
                'CorrAddressLine02' => $_POST['CORR_ADDRESS_LINE_2'] ?? '',
                'CorrAddressLine03' => $_POST['CORR_ADDRESS_LINE_3'] ?? '',
                'CorrAddressTown' => $_POST['CORR_ADDRESS_TOWN'] ?? '',
                'CorrAddressDistrict' => $_POST['CORR_ADDRESS_DISTRICT'] ?? '',
                'BankAccountNo' => $_POST['BANK_ACCOUNT_NO'],
                'BankCode' => $_POST['BANK_CODE'],
                'BankBranch' => $_POST['BANK_BRANCH'],
                'BankAccountType' => $_POST['BANK_ACCOUNT_TYPE'],
                'EmployeStatus' => $_POST['EMPLOYE_STATUS'],
                'Occupation' => $_POST['OCCUPATION'] ?? '',
                'NameOfEmployer' => $_POST['NAME_OF_EMPLOYER'] ?? '',
                'AddressOfEmployer' => $_POST['ADDRESS_OF_EMPLOYER'] ?? '',
                'OfficePhoneNo' => $_POST['OFFICE_PHONE_NO'] ?? '',
                'OfficeEmail' => $_POST['OFFICE_EMAIL'] ?? '',
                'EmployeeComment' => $_POST['EMPLOYEE_COMMENT'] ?? '',
                'NameOfBusiness' => $_POST['NAME_OF_BUSINESS'] ?? '',
                'AddressOfBusiness' => $_POST['ADDRESS_OF_BUSINESS'] ?? '',
                'OtherConnBusinessStatus' => $_POST['OTHER_CONN_BUSINESS_STATUS'] ?? '',
                'OtherConnBusinessDesc' => $_POST['OTHER_CONN_BUSINESS_DESC'] ?? '',
                'ExpValueInvestment' => $_POST['EXP_VALUE_INVESTMENT'],
                'SourseOfFund' => $_POST['SOURCE_OF_FUNDS'] ?? '',
                'UsaPersonStatus' => $_POST['USA_PERSON_STATUS'] ?? '',
                'UsaTaxIdentificationNo' => $_POST['USA_TAX_IDENTIFI_NO'] ?? '',
                'FactaDeclaration' => $_POST['FACTA_DECLARATION'] ?? '',
                'DualCitizenship' => $_POST['DUAL_CITIZENSHIP'] ?? '',
                'DualCitizenCountry' => $_POST['DUAL_CITIZEN_COUNTRY'] ?? '',
                'DualCitizenPassport' => $_POST['DUAL_CITIZEN_PASSPORT'] ?? '',
                'IsPEP' => $_POST['IS_PEP'],
                'PepQ1' => $_POST['PEP_Q1'],
                'PepQ1Details' => $_POST['PEP_Q1_DETAILS'] ?? '',
                'PepQ2' => $_POST['PEP_Q2'],
                'PepQ2Details' => $_POST['PEP_Q2_DETAILS'] ?? '',
                'PepQ3' => $_POST['PEP_Q3'],
                'PepQ3Details' => $_POST['PEP_Q3_DETAILS'] ?? '',
                'PepQ4' => $_POST['PEP_Q4'],
                'PepQ4Detailas' => $_POST['PEP_Q4_DETAILS'] ?? '',
                'LatigationStatus' => $_POST['LITIGATION_STATUS'],
                'LatigationDetails' => $_POST['LITIGATION_DETAILS'] ?? '',
                'EnterUser' => $_POST['ENTER_USER'],
                'EnterDate' => $_POST['ENTER_DATE'] ?? '',
                'CountryOfResidency' => $_POST['COUNTRY_OF_RESIDENCY'],
                'Nationality' => $_POST['NATIONALITY'],
                'Residency' => $_POST['RESIDENCY'],
                'IsLKPassport' => $_POST['IS_LK_PASSPORT'] ?? '',
                'InvestorId' => $_POST['INVESTOR_ID'] ?? '',
                'InvestmentOb' => $_POST['INVESTMENT_OB'] ?? '',
                'InvestmentStrategy' => $_POST['INVESTMENT_STRATEGY'] ?? '',
            ];

            // Send request to SaveUser
            $url = API_BASE_URL . '/api/User/SaveUser';
            $jsonData = json_encode($postData, JSON_UNESCAPED_SLASHES);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Bearer ' . $token,
                'Content-Length: ' . strlen($jsonData)
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                $result = ['error' => 'cURL error: ' . $curlError];
            } elseif ($httpCode === 200) {
                $result = ['success' => 'Account created. Account ID: ' . $response];
            } else {
                $result = ['error' => 'HTTP ' . $httpCode . ': ' . $response];
            }
        }
    } else {
        $result = ['error' => implode('<br>', $errors)];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>CDS Account Opening (Required Fields Only)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 10px; }
        label { display: inline-block; width: 200px; vertical-align: top; }
        input, select, textarea { width: 300px; }
        .required { color: red; }
        hr { margin: 20px 0; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .sample-btn { margin-bottom: 20px; padding: 10px 20px; font-size: 16px; cursor: pointer; }
        .note { font-size: 0.9em; color: #555; }
    </style>
    <script>
    function fillSampleData() {
        const sample = {
            USER_ID: 'abc@gmail.com',
            TITLE: 'MR.',
            INITIALS: 'S.K.C.S.J.',
            SURNAME: 'JAYAWARDANA',
            NAMES_DENO_INITIALS: 'SUDUWELI KONDAGE CHANDIKA SUDANTHA',
            MOBILE_NO: '0761234567',
            TELEPHONE: '',
            EMAIL_ADDRESS: 'abc@gmail.com',
            NIC_NO: '891234567V',
            PASSPORT_NO: '',
            PASSPORT_EXP_DATE: '',
            DATE_OF_BIRTH: '1989/05/10',
            GENDER: 'M',
            STOCK_BROKER_FIRM: 'BMS',
            EXIST_CDS_ACCOUNT: 'Y',
            CDS_ACCOUNT_NO: '123511-LI',
            TIN_NO: '',
            RES_ADDRESS_STATUS: 'Y',
            RES_ADDRESS_STATUS_DESC: '2',
            RES_ADDRESS_LINE_1: '79/7 KOLAMUNNA',
            RES_ADDRESS_LINE_2: 'AMPAN, KUDATHTHANAI',
            RES_ADDRESS_LINE_3: '',
            RES_ADDRESS_TOWN: 'PILIYANDALA',
            RES_ADDRESS_DISTRICT: '17',
            COUNTRY: 'LK',
            CORR_ADDRESS_STATUS: 'Y',
            CORR_ADDRESS_LINE_1: '',
            CORR_ADDRESS_LINE_2: '',
            CORR_ADDRESS_LINE_3: '',
            CORR_ADDRESS_TOWN: '',
            CORR_ADDRESS_DISTRICT: '',
            BANK_ACCOUNT_NO: '123456790',
            BANK_CODE: '7056',
            BANK_BRANCH: '108',
            BANK_ACCOUNT_TYPE: 'I',
            EMPLOYE_STATUS: 'N',
            OCCUPATION: 'FARMER',
            NAME_OF_EMPLOYER: '',
            ADDRESS_OF_EMPLOYER: '',
            OFFICE_PHONE_NO: '',
            OFFICE_EMAIL: '',
            EMPLOYEE_COMMENT: '',
            NAME_OF_BUSINESS: 'FARMING',
            ADDRESS_OF_BUSINESS: '',
            OTHER_CONN_BUSINESS_STATUS: 'N',
            OTHER_CONN_BUSINESS_DESC: '',
            EXP_VALUE_INVESTMENT: '2',
            SOURCE_OF_FUNDS: '',
            USA_PERSON_STATUS: 'N',
            USA_TAX_IDENTIFI_NO: '',
            FACTA_DECLARATION: 'N',
            DUAL_CITIZENSHIP: 'N',
            DUAL_CITIZEN_COUNTRY: '',
            DUAL_CITIZEN_PASSPORT: '',
            IS_PEP: 'N',
            PEP_Q1: 'N',
            PEP_Q1_DETAILS: '',
            PEP_Q2: 'N',
            PEP_Q2_DETAILS: '',
            PEP_Q3: 'N',
            PEP_Q3_DETAILS: '',
            PEP_Q4: 'N',
            PEP_Q4_DETAILS: '',
            LITIGATION_STATUS: 'N',
            LITIGATION_DETAILS: '',
            ENTER_USER: 'abc@gmail.com',
            ENTER_DATE: '',
            COUNTRY_OF_RESIDENCY: 'LK',
            NATIONALITY: 'LK',
            RESIDENCY: 'R',
            IS_LK_PASSPORT: '',
            INVESTOR_ID: '12',
            INVESTMENT_OB: '',
            INVESTMENT_STRATEGY: ''
        };

        for (let field in sample) {
            let input = document.querySelector(`[name="${field}"]`);
            if (!input) continue;
            if (input.tagName === 'SELECT') {
                input.value = sample[field];
            } else if (input.tagName === 'INPUT') {
                if (input.type === 'radio') {
                    let radio = document.querySelector(`[name="${field}"][value="${sample[field]}"]`);
                    if (radio) radio.checked = true;
                } else if (input.type === 'text' || input.type === 'email' || input.type === 'hidden') {
                    input.value = sample[field];
                }
            } else if (input.tagName === 'TEXTAREA') {
                input.value = sample[field];
            }
        }
    }
    </script>
</head>
<body>
    <h1>Open CDS Account (API Test) – Required Fields Only</h1>

    <button class="sample-btn" onclick="fillSampleData()">Fill with Sample Data</button>

    <?php if ($result): ?>
        <div class="<?php echo isset($result['error']) ? 'error' : 'success'; ?>">
            <strong>API Response:</strong>
            <pre><?php print_r($result); ?></pre>
        </div>
        <hr>
    <?php endif; ?>

    <form method="post">
        <p>Fields marked <span class="required">*</span> are required (based on Excel).</p>

        <!-- Account info -->
        <fieldset>
            <legend>Account Information</legend>
            <div class="form-group">
                <label>User ID (Email) <span class="required">*</span></label>
                <input type="email" name="USER_ID" required value="<?php echo htmlspecialchars($_POST['USER_ID'] ?? 'abc@gmail.com'); ?>">
            </div>
            <div class="form-group">
                <label>Title <span class="required">*</span></label>
                <input type="text" name="TITLE" required value="<?php echo htmlspecialchars($_POST['TITLE'] ?? 'MR.'); ?>">
                <small>(e.g., MR., MRS., MS.)</small>
            </div>
            <div class="form-group">
                <label>Initials <span class="required">*</span></label>
                <input type="text" name="INITIALS" required value="<?php echo htmlspecialchars($_POST['INITIALS'] ?? 'A.B.C.'); ?>">
            </div>
            <div class="form-group">
                <label>Surname <span class="required">*</span></label>
                <input type="text" name="SURNAME" required value="<?php echo htmlspecialchars($_POST['SURNAME'] ?? 'Surname'); ?>">
            </div>
            <div class="form-group">
                <label>Full Name (as per NIC) <span class="required">*</span></label>
                <input type="text" name="NAMES_DENO_INITIALS" required value="<?php echo htmlspecialchars($_POST['NAMES_DENO_INITIALS'] ?? 'FIRST MIDDLE LAST'); ?>">
            </div>
            <div class="form-group">
                <label>Mobile No <span class="required">*</span></label>
                <input type="text" name="MOBILE_NO" required pattern="[0-9]{10}" title="10 digit mobile number" value="<?php echo htmlspecialchars($_POST['MOBILE_NO'] ?? '0761234567'); ?>">
            </div>
            <div class="form-group">
                <label>Telephone (optional)</label>
                <input type="text" name="TELEPHONE" value="<?php echo htmlspecialchars($_POST['TELEPHONE'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" name="EMAIL_ADDRESS" required value="<?php echo htmlspecialchars($_POST['EMAIL_ADDRESS'] ?? 'abc@gmail.com'); ?>">
            </div>
        </fieldset>

        <!-- Identity -->
        <fieldset>
            <legend>Identity</legend>
            <div class="form-group">
                <label>NIC No <span class="required">*</span></label>
                <input type="text" name="NIC_NO" required pattern="^([0-9]{9}[Vv]|[0-9]{12})$" title="9 digits + V or 12 digits" value="<?php echo htmlspecialchars($_POST['NIC_NO'] ?? '891234567V'); ?>">
            </div>
            <div class="form-group">
                <label>Passport No (optional)</label>
                <input type="text" name="PASSPORT_NO" value="<?php echo htmlspecialchars($_POST['PASSPORT_NO'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Passport Exp Date (optional)</label>
                <input type="text" name="PASSPORT_EXP_DATE" placeholder="yyyy/mm/dd" pattern="\d{4}/\d{2}/\d{2}" title="yyyy/mm/dd" value="<?php echo htmlspecialchars($_POST['PASSPORT_EXP_DATE'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Date of Birth <span class="required">*</span></label>
                <input type="text" name="DATE_OF_BIRTH" required placeholder="yyyy/mm/dd" pattern="\d{4}/\d{2}/\d{2}" title="yyyy/mm/dd" value="<?php echo htmlspecialchars($_POST['DATE_OF_BIRTH'] ?? '1989/05/10'); ?>">
            </div>
            <div class="form-group">
                <label>Gender <span class="required">*</span></label>
                <input type="radio" name="GENDER" value="M" <?php echo (isset($_POST['GENDER']) && $_POST['GENDER']=='M') ? 'checked' : 'checked'; ?> required> Male
                <input type="radio" name="GENDER" value="F" <?php echo (isset($_POST['GENDER']) && $_POST['GENDER']=='F') ? 'checked' : ''; ?> required> Female
            </div>
        </fieldset>

        <!-- Broker & CDS -->
        <fieldset>
            <legend>Broker & CDS</legend>
            <div class="form-group">
                <label>Stock Broker Firm <span class="required">*</span></label>
                <input type="text" name="STOCK_BROKER_FIRM" required value="<?php echo htmlspecialchars($_POST['STOCK_BROKER_FIRM'] ?? 'BMS'); ?>">
                <small>(Broker code)</small>
            </div>
            <div class="form-group">
                <label>Exist CDS Account? (optional)</label>
                <input type="radio" name="EXIST_CDS_ACCOUNT" value="Y" <?php echo (isset($_POST['EXIST_CDS_ACCOUNT']) && $_POST['EXIST_CDS_ACCOUNT']=='Y') ? 'checked' : ''; ?>> Yes
                <input type="radio" name="EXIST_CDS_ACCOUNT" value="N" <?php echo (isset($_POST['EXIST_CDS_ACCOUNT']) && $_POST['EXIST_CDS_ACCOUNT']=='N') ? 'checked' : 'checked'; ?>> No
            </div>
            <div class="form-group">
                <label>CDS Account No (optional)</label>
                <input type="text" name="CDS_ACCOUNT_NO" value="<?php echo htmlspecialchars($_POST['CDS_ACCOUNT_NO'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>TIN No (optional)</label>
                <input type="text" name="TIN_NO" value="<?php echo htmlspecialchars($_POST['TIN_NO'] ?? ''); ?>">
            </div>
        </fieldset>

        <!-- Residential Address -->
        <fieldset>
            <legend>Residential Address</legend>
            <div class="form-group">
                <label>Res Address Status (Y/N) <span class="required">*</span></label>
                <input type="radio" name="RES_ADDRESS_STATUS" value="Y" <?php echo (isset($_POST['RES_ADDRESS_STATUS']) && $_POST['RES_ADDRESS_STATUS']=='Y') ? 'checked' : 'checked'; ?> required> Yes
                <input type="radio" name="RES_ADDRESS_STATUS" value="N" <?php echo (isset($_POST['RES_ADDRESS_STATUS']) && $_POST['RES_ADDRESS_STATUS']=='N') ? 'checked' : ''; ?> required> No
            </div>
            <div class="form-group">
                <label>Res Address Status Desc <span class="required">*</span></label>
                <select name="RES_ADDRESS_STATUS_DESC" required>
                    <option value="">Select</option>
                    <option value="1" <?php echo (isset($_POST['RES_ADDRESS_STATUS_DESC']) && $_POST['RES_ADDRESS_STATUS_DESC']=='1') ? 'selected' : ''; ?>>1 - Owner</option>
                    <option value="2" <?php echo (isset($_POST['RES_ADDRESS_STATUS_DESC']) && $_POST['RES_ADDRESS_STATUS_DESC']=='2') ? 'selected' : ''; ?>>2 - With parents</option>
                    <option value="3" <?php echo (isset($_POST['RES_ADDRESS_STATUS_DESC']) && $_POST['RES_ADDRESS_STATUS_DESC']=='3') ? 'selected' : ''; ?>>3 - Lease / Rent</option>
                    <option value="4" <?php echo (isset($_POST['RES_ADDRESS_STATUS_DESC']) && $_POST['RES_ADDRESS_STATUS_DESC']=='4') ? 'selected' : ''; ?>>4 - Friend’s / Relative’s</option>
                    <option value="5" <?php echo (isset($_POST['RES_ADDRESS_STATUS_DESC']) && $_POST['RES_ADDRESS_STATUS_DESC']=='5') ? 'selected' : ''; ?>>5 - Board / Lodging</option>
                    <option value="6" <?php echo (isset($_POST['RES_ADDRESS_STATUS_DESC']) && $_POST['RES_ADDRESS_STATUS_DESC']=='6') ? 'selected' : ''; ?>>6 - Official</option>
                </select>
            </div>
            <div class="form-group">
                <label>Res Address Line 1 <span class="required">*</span></label>
                <input type="text" name="RES_ADDRESS_LINE_1" required value="<?php echo htmlspecialchars($_POST['RES_ADDRESS_LINE_1'] ?? '79/7 KOLAMUNNA'); ?>">
            </div>
            <div class="form-group">
                <label>Res Address Line 2 (optional)</label>
                <input type="text" name="RES_ADDRESS_LINE_2" value="<?php echo htmlspecialchars($_POST['RES_ADDRESS_LINE_2'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Res Address Line 3 (optional)</label>
                <input type="text" name="RES_ADDRESS_LINE_3" value="<?php echo htmlspecialchars($_POST['RES_ADDRESS_LINE_3'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Res Address Town <span class="required">*</span></label>
                <input type="text" name="RES_ADDRESS_TOWN" required value="<?php echo htmlspecialchars($_POST['RES_ADDRESS_TOWN'] ?? 'PILIYANDALA'); ?>">
            </div>
            <div class="form-group">
                <label>Res Address District <span class="required">*</span></label>
                <input type="text" name="RES_ADDRESS_DISTRICT" required value="<?php echo htmlspecialchars($_POST['RES_ADDRESS_DISTRICT'] ?? '17'); ?>">
                <small>(District code, e.g., 17)</small>
            </div>
            <div class="form-group">
                <label>Country <span class="required">*</span></label>
                <input type="text" name="COUNTRY" required value="<?php echo htmlspecialchars($_POST['COUNTRY'] ?? 'LK'); ?>">
            </div>
        </fieldset>

        <!-- Correspondence Address (optional) -->
        <fieldset>
            <legend>Correspondence Address (all optional)</legend>
            <div class="form-group">
                <label>Corr Address Status</label>
                <input type="radio" name="CORR_ADDRESS_STATUS" value="Y" <?php echo (isset($_POST['CORR_ADDRESS_STATUS']) && $_POST['CORR_ADDRESS_STATUS']=='Y') ? 'checked' : ''; ?>> Yes
                <input type="radio" name="CORR_ADDRESS_STATUS" value="N" <?php echo (isset($_POST['CORR_ADDRESS_STATUS']) && $_POST['CORR_ADDRESS_STATUS']=='N') ? 'checked' : 'checked'; ?>> No
            </div>
            <div class="form-group">
                <label>Corr Address Line 1</label>
                <input type="text" name="CORR_ADDRESS_LINE_1" value="<?php echo htmlspecialchars($_POST['CORR_ADDRESS_LINE_1'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Corr Address Line 2</label>
                <input type="text" name="CORR_ADDRESS_LINE_2" value="<?php echo htmlspecialchars($_POST['CORR_ADDRESS_LINE_2'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Corr Address Line 3</label>
                <input type="text" name="CORR_ADDRESS_LINE_3" value="<?php echo htmlspecialchars($_POST['CORR_ADDRESS_LINE_3'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Corr Address Town</label>
                <input type="text" name="CORR_ADDRESS_TOWN" value="<?php echo htmlspecialchars($_POST['CORR_ADDRESS_TOWN'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Corr Address District</label>
                <input type="text" name="CORR_ADDRESS_DISTRICT" value="<?php echo htmlspecialchars($_POST['CORR_ADDRESS_DISTRICT'] ?? ''); ?>">
            </div>
        </fieldset>

        <!-- Bank Details -->
        <fieldset>
            <legend>Bank Details</legend>
            <div class="form-group">
                <label>Bank Account No <span class="required">*</span></label>
                <input type="text" name="BANK_ACCOUNT_NO" required value="<?php echo htmlspecialchars($_POST['BANK_ACCOUNT_NO'] ?? '123456790'); ?>">
            </div>
            <div class="form-group">
                <label>Bank Code <span class="required">*</span></label>
                <input type="text" name="BANK_CODE" required value="<?php echo htmlspecialchars($_POST['BANK_CODE'] ?? '7056'); ?>">
            </div>
            <div class="form-group">
                <label>Bank Branch <span class="required">*</span></label>
                <input type="text" name="BANK_BRANCH" required value="<?php echo htmlspecialchars($_POST['BANK_BRANCH'] ?? '108'); ?>">
            </div>
            <div class="form-group">
                <label>Bank Account Type <span class="required">*</span></label>
                <input type="radio" name="BANK_ACCOUNT_TYPE" value="I" <?php echo (isset($_POST['BANK_ACCOUNT_TYPE']) && $_POST['BANK_ACCOUNT_TYPE']=='I') ? 'checked' : 'checked'; ?> required> I (IIA)
                <input type="radio" name="BANK_ACCOUNT_TYPE" value="C" <?php echo (isset($_POST['BANK_ACCOUNT_TYPE']) && $_POST['BANK_ACCOUNT_TYPE']=='C') ? 'checked' : ''; ?> required> C (CTRA)
            </div>
        </fieldset>

        <!-- Employment -->
        <fieldset>
            <legend>Employment</legend>
            <div class="form-group">
                <label>Employe Status <span class="required">*</span></label>
                <input type="radio" name="EMPLOYE_STATUS" value="Y" <?php echo (isset($_POST['EMPLOYE_STATUS']) && $_POST['EMPLOYE_STATUS']=='Y') ? 'checked' : ''; ?> required> Y (Employed)
                <input type="radio" name="EMPLOYE_STATUS" value="N" <?php echo (isset($_POST['EMPLOYE_STATUS']) && $_POST['EMPLOYE_STATUS']=='N') ? 'checked' : 'checked'; ?> required> N (Not employed)
                <input type="radio" name="EMPLOYE_STATUS" value="S" <?php echo (isset($_POST['EMPLOYE_STATUS']) && $_POST['EMPLOYE_STATUS']=='S') ? 'checked' : ''; ?> required> S (Self employed)
                <input type="radio" name="EMPLOYE_STATUS" value="T" <?php echo (isset($_POST['EMPLOYE_STATUS']) && $_POST['EMPLOYE_STATUS']=='T') ? 'checked' : ''; ?> required> T (Retired)
            </div>
            <div class="form-group">
                <label>Occupation (optional)</label>
                <input type="text" name="OCCUPATION" value="<?php echo htmlspecialchars($_POST['OCCUPATION'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Name of Employer (optional)</label>
                <input type="text" name="NAME_OF_EMPLOYER" value="<?php echo htmlspecialchars($_POST['NAME_OF_EMPLOYER'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Address of Employer (optional)</label>
                <input type="text" name="ADDRESS_OF_EMPLOYER" value="<?php echo htmlspecialchars($_POST['ADDRESS_OF_EMPLOYER'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Office Phone No (optional)</label>
                <input type="text" name="OFFICE_PHONE_NO" value="<?php echo htmlspecialchars($_POST['OFFICE_PHONE_NO'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Office Email (optional)</label>
                <input type="email" name="OFFICE_EMAIL" value="<?php echo htmlspecialchars($_POST['OFFICE_EMAIL'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Employee Comment (optional)</label>
                <textarea name="EMPLOYEE_COMMENT"><?php echo htmlspecialchars($_POST['EMPLOYEE_COMMENT'] ?? ''); ?></textarea>
            </div>
        </fieldset>

        <!-- Business (optional) -->
        <fieldset>
            <legend>Business (optional)</legend>
            <div class="form-group">
                <label>Name of Business</label>
                <input type="text" name="NAME_OF_BUSINESS" value="<?php echo htmlspecialchars($_POST['NAME_OF_BUSINESS'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Address of Business</label>
                <input type="text" name="ADDRESS_OF_BUSINESS" value="<?php echo htmlspecialchars($_POST['ADDRESS_OF_BUSINESS'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Other Conn Business Status</label>
                <input type="radio" name="OTHER_CONN_BUSINESS_STATUS" value="Y" <?php echo (isset($_POST['OTHER_CONN_BUSINESS_STATUS']) && $_POST['OTHER_CONN_BUSINESS_STATUS']=='Y') ? 'checked' : ''; ?>> Yes
                <input type="radio" name="OTHER_CONN_BUSINESS_STATUS" value="N" <?php echo (isset($_POST['OTHER_CONN_BUSINESS_STATUS']) && $_POST['OTHER_CONN_BUSINESS_STATUS']=='N') ? 'checked' : 'checked'; ?>> No
            </div>
            <div class="form-group">
                <label>Other Conn Business Desc</label>
                <input type="text" name="OTHER_CONN_BUSINESS_DESC" value="<?php echo htmlspecialchars($_POST['OTHER_CONN_BUSINESS_DESC'] ?? ''); ?>">
            </div>
        </fieldset>

        <!-- Investment -->
        <fieldset>
            <legend>Investment</legend>
            <div class="form-group">
                <label>Exp Value Investment <span class="required">*</span></label>
                <select name="EXP_VALUE_INVESTMENT" required>
                    <option value="">Select</option>
                    <option value="1" <?php echo (isset($_POST['EXP_VALUE_INVESTMENT']) && $_POST['EXP_VALUE_INVESTMENT']=='1') ? 'selected' : ''; ?>>1 - Less than Rs. 100,000</option>
                    <option value="2" <?php echo (isset($_POST['EXP_VALUE_INVESTMENT']) && $_POST['EXP_VALUE_INVESTMENT']=='2') ? 'selected' : ''; ?>>2 - Rs 100,000 to Rs 500,000</option>
                    <option value="3" <?php echo (isset($_POST['EXP_VALUE_INVESTMENT']) && $_POST['EXP_VALUE_INVESTMENT']=='3') ? 'selected' : ''; ?>>3 - Rs 500,000 to Rs 1,000,000</option>
                    <option value="4" <?php echo (isset($_POST['EXP_VALUE_INVESTMENT']) && $_POST['EXP_VALUE_INVESTMENT']=='4') ? 'selected' : ''; ?>>4 - Rs 1,000,000 to Rs 2,000,000</option>
                    <option value="5" <?php echo (isset($_POST['EXP_VALUE_INVESTMENT']) && $_POST['EXP_VALUE_INVESTMENT']=='5') ? 'selected' : ''; ?>>5 - Rs 2,000,000 to Rs 3,000,000</option>
                    <option value="6" <?php echo (isset($_POST['EXP_VALUE_INVESTMENT']) && $_POST['EXP_VALUE_INVESTMENT']=='6') ? 'selected' : ''; ?>>6 - Rs 3,000,000 to Rs 4,000,000</option>
                    <option value="7" <?php echo (isset($_POST['EXP_VALUE_INVESTMENT']) && $_POST['EXP_VALUE_INVESTMENT']=='7') ? 'selected' : ''; ?>>7 - Rs 4,000,000 toRs 5,000,000</option>
                    <option value="8" <?php echo (isset($_POST['EXP_VALUE_INVESTMENT']) && $_POST['EXP_VALUE_INVESTMENT']=='8') ? 'selected' : ''; ?>>8 - Rs 5,000,000 to Rs 10,000,000</option>
                    <option value="9" <?php echo (isset($_POST['EXP_VALUE_INVESTMENT']) && $_POST['EXP_VALUE_INVESTMENT']=='9') ? 'selected' : ''; ?>>9 - Over Rs 10,000,000</option>
                </select>
            </div>
            <div class="form-group">
                <label>Source of Funds (optional)</label>
                <input type="text" name="SOURCE_OF_FUNDS" value="<?php echo htmlspecialchars($_POST['SOURCE_OF_FUNDS'] ?? ''); ?>">
                <small>(numeric code?)</small>
            </div>
        </fieldset>

        <!-- FATCA / PEP -->
        <fieldset>
            <legend>FATCA / PEP</legend>
            <div class="form-group">
                <label>USA Person Status (optional)</label>
                <input type="radio" name="USA_PERSON_STATUS" value="Y" <?php echo (isset($_POST['USA_PERSON_STATUS']) && $_POST['USA_PERSON_STATUS']=='Y') ? 'checked' : ''; ?>> Yes
                <input type="radio" name="USA_PERSON_STATUS" value="N" <?php echo (isset($_POST['USA_PERSON_STATUS']) && $_POST['USA_PERSON_STATUS']=='N') ? 'checked' : 'checked'; ?>> No
            </div>
            <div class="form-group">
                <label>USA Tax Identifier No (optional)</label>
                <input type="text" name="USA_TAX_IDENTIFI_NO" value="<?php echo htmlspecialchars($_POST['USA_TAX_IDENTIFI_NO'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>FACTA Declaration (optional)</label>
                <input type="radio" name="FACTA_DECLARATION" value="Y" <?php echo (isset($_POST['FACTA_DECLARATION']) && $_POST['FACTA_DECLARATION']=='Y') ? 'checked' : ''; ?>> Yes
                <input type="radio" name="FACTA_DECLARATION" value="N" <?php echo (isset($_POST['FACTA_DECLARATION']) && $_POST['FACTA_DECLARATION']=='N') ? 'checked' : 'checked'; ?>> No
            </div>
            <div class="form-group">
                <label>Dual Citizenship (optional)</label>
                <input type="radio" name="DUAL_CITIZENSHIP" value="Y" <?php echo (isset($_POST['DUAL_CITIZENSHIP']) && $_POST['DUAL_CITIZENSHIP']=='Y') ? 'checked' : ''; ?>> Yes
                <input type="radio" name="DUAL_CITIZENSHIP" value="N" <?php echo (isset($_POST['DUAL_CITIZENSHIP']) && $_POST['DUAL_CITIZENSHIP']=='N') ? 'checked' : 'checked'; ?>> No
            </div>
            <div class="form-group">
                <label>Dual Citizen Country (optional)</label>
                <input type="text" name="DUAL_CITIZEN_COUNTRY" value="<?php echo htmlspecialchars($_POST['DUAL_CITIZEN_COUNTRY'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Dual Citizen Passport (optional)</label>
                <input type="text" name="DUAL_CITIZEN_PASSPORT" value="<?php echo htmlspecialchars($_POST['DUAL_CITIZEN_PASSPORT'] ?? ''); ?>">
            </div>
        </fieldset>

        <fieldset>
            <legend>PEP Questions</legend>
            <div class="form-group">
                <label>Is PEP? <span class="required">*</span></label>
                <input type="radio" name="IS_PEP" value="Y" <?php echo (isset($_POST['IS_PEP']) && $_POST['IS_PEP']=='Y') ? 'checked' : ''; ?> required> Yes
                <input type="radio" name="IS_PEP" value="N" <?php echo (isset($_POST['IS_PEP']) && $_POST['IS_PEP']=='N') ? 'checked' : 'checked'; ?> required> No
            </div>
            <div class="form-group">
                <label>PEP Q1 <span class="required">*</span></label>
                <input type="radio" name="PEP_Q1" value="Y" <?php echo (isset($_POST['PEP_Q1']) && $_POST['PEP_Q1']=='Y') ? 'checked' : ''; ?> required> Yes
                <input type="radio" name="PEP_Q1" value="N" <?php echo (isset($_POST['PEP_Q1']) && $_POST['PEP_Q1']=='N') ? 'checked' : 'checked'; ?> required> No
            </div>
            <div class="form-group">
                <label>PEP Q1 Details (optional)</label>
                <input type="text" name="PEP_Q1_DETAILS" value="<?php echo htmlspecialchars($_POST['PEP_Q1_DETAILS'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>PEP Q2 <span class="required">*</span></label>
                <input type="radio" name="PEP_Q2" value="Y" <?php echo (isset($_POST['PEP_Q2']) && $_POST['PEP_Q2']=='Y') ? 'checked' : ''; ?> required> Yes
                <input type="radio" name="PEP_Q2" value="N" <?php echo (isset($_POST['PEP_Q2']) && $_POST['PEP_Q2']=='N') ? 'checked' : 'checked'; ?> required> No
            </div>
            <div class="form-group">
                <label>PEP Q2 Details (optional)</label>
                <input type="text" name="PEP_Q2_DETAILS" value="<?php echo htmlspecialchars($_POST['PEP_Q2_DETAILS'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>PEP Q3 <span class="required">*</span></label>
                <input type="radio" name="PEP_Q3" value="Y" <?php echo (isset($_POST['PEP_Q3']) && $_POST['PEP_Q3']=='Y') ? 'checked' : ''; ?> required> Yes
                <input type="radio" name="PEP_Q3" value="N" <?php echo (isset($_POST['PEP_Q3']) && $_POST['PEP_Q3']=='N') ? 'checked' : 'checked'; ?> required> No
            </div>
            <div class="form-group">
                <label>PEP Q3 Details (optional)</label>
                <input type="text" name="PEP_Q3_DETAILS" value="<?php echo htmlspecialchars($_POST['PEP_Q3_DETAILS'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>PEP Q4 <span class="required">*</span></label>
                <input type="radio" name="PEP_Q4" value="Y" <?php echo (isset($_POST['PEP_Q4']) && $_POST['PEP_Q4']=='Y') ? 'checked' : ''; ?> required> Yes
                <input type="radio" name="PEP_Q4" value="N" <?php echo (isset($_POST['PEP_Q4']) && $_POST['PEP_Q4']=='N') ? 'checked' : 'checked'; ?> required> No
            </div>
            <div class="form-group">
                <label>PEP Q4 Details (optional)</label>
                <input type="text" name="PEP_Q4_DETAILS" value="<?php echo htmlspecialchars($_POST['PEP_Q4_DETAILS'] ?? ''); ?>">
            </div>
        </fieldset>

        <fieldset>
            <legend>Litigation</legend>
            <div class="form-group">
                <label>Litigation Status <span class="required">*</span></label>
                <input type="radio" name="LITIGATION_STATUS" value="Y" <?php echo (isset($_POST['LITIGATION_STATUS']) && $_POST['LITIGATION_STATUS']=='Y') ? 'checked' : ''; ?> required> Yes
                <input type="radio" name="LITIGATION_STATUS" value="N" <?php echo (isset($_POST['LITIGATION_STATUS']) && $_POST['LITIGATION_STATUS']=='N') ? 'checked' : 'checked'; ?> required> No
            </div>
            <div class="form-group">
                <label>Litigation Details (optional)</label>
                <input type="text" name="LITIGATION_DETAILS" value="<?php echo htmlspecialchars($_POST['LITIGATION_DETAILS'] ?? ''); ?>">
            </div>
        </fieldset>

        <fieldset>
            <legend>Additional Info</legend>
            <div class="form-group">
                <label>Enter User (email) <span class="required">*</span></label>
                <input type="email" name="ENTER_USER" required value="<?php echo htmlspecialchars($_POST['ENTER_USER'] ?? 'abc@gmail.com'); ?>">
            </div>
            <div class="form-group">
                <label>Enter Date (optional, yyyy/mm/dd)</label>
                <input type="text" name="ENTER_DATE" placeholder="yyyy/mm/dd" pattern="\d{4}/\d{2}/\d{2}" title="yyyy/mm/dd" value="<?php echo htmlspecialchars($_POST['ENTER_DATE'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Country of Residency <span class="required">*</span></label>
                <input type="text" name="COUNTRY_OF_RESIDENCY" required value="<?php echo htmlspecialchars($_POST['COUNTRY_OF_RESIDENCY'] ?? 'LK'); ?>">
            </div>
            <div class="form-group">
                <label>Nationality <span class="required">*</span></label>
                <input type="text" name="NATIONALITY" required value="<?php echo htmlspecialchars($_POST['NATIONALITY'] ?? 'LK'); ?>">
            </div>
            <div class="form-group">
                <label>Residency (R/N) <span class="required">*</span></label>
                <input type="radio" name="RESIDENCY" value="R" <?php echo (isset($_POST['RESIDENCY']) && $_POST['RESIDENCY']=='R') ? 'checked' : 'checked'; ?> required> R (Resident)
                <input type="radio" name="RESIDENCY" value="N" <?php echo (isset($_POST['RESIDENCY']) && $_POST['RESIDENCY']=='N') ? 'checked' : ''; ?> required> N (Non-resident)
            </div>
            <div class="form-group">
                <label>Is LK Passport? (optional)</label>
                <input type="radio" name="IS_LK_PASSPORT" value="Y" <?php echo (isset($_POST['IS_LK_PASSPORT']) && $_POST['IS_LK_PASSPORT']=='Y') ? 'checked' : ''; ?>> Yes
                <input type="radio" name="IS_LK_PASSPORT" value="N" <?php echo (isset($_POST['IS_LK_PASSPORT']) && $_POST['IS_LK_PASSPORT']=='N') ? 'checked' : 'checked'; ?>> No
            </div>
            <div class="form-group">
                <label>Investor ID (optional)</label>
                <input type="text" name="INVESTOR_ID" value="<?php echo htmlspecialchars($_POST['INVESTOR_ID'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Investment Ob (optional)</label>
                <input type="text" name="INVESTMENT_OB" value="<?php echo htmlspecialchars($_POST['INVESTMENT_OB'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Investment Strategy (optional)</label>
                <input type="text" name="INVESTMENT_STRATEGY" value="<?php echo htmlspecialchars($_POST['INVESTMENT_STRATEGY'] ?? ''); ?>">
            </div>
        </fieldset>

        <button type="submit">Submit to API</button>
    </form>

    <hr>
    <p><strong>Note:</strong> All required fields are marked and validated. The API response will appear at the top.</p>
</body>
</html>