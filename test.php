<?php
// ==================== CONFIG ====================
define('API_BASE_URL', 'https://uat-cseapi.cse.lk');
define('API_USERNAME', 'SCTestUser');
define('API_PASSWORD', '2d26tF&M!cqS');

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

// ==================== SAVE USER TEST ====================
function testSaveUser($token) {
    echo "\n=== Testing SaveUser Endpoint ===\n";
    
    // Generate unique UserID and timestamp for test
    $timestamp = date('YmdHis');
    $random = rand(100, 999);
    $testUserId = "TEST_" . $timestamp . $random; // Shorter UserID
    $testApiRef = "REF_" . $timestamp . $random;  // Shorter API Ref
    
    // Test data with FIXED field lengths
    $userData = [
        'AccountID' => 0,
        'UserID' => $testUserId,
        'Title' => 'Mr',
        'Initials' => 'T',
        'Surname' => 'Test',
        'NameDenoInitials' => 'TI',  // Shorter
        'MobileNo' => '0712345678',  // 10 digits
        'TelphoneNo' => '011234567', // 9 digits
        'Email' => 'test@test.com',  // Shorter email
        'IdentificationProof' => 'P',
        'NicNo' => '931234567V',
        'PassportNo' => '',
        'PassportExpDate' => '',
        'DateOfBirthday' => '1990/01/15',
        'Gender' => 'M',
        // 🔴 CRITICAL FIX: Broker code must be 3 chars or less!
        'BrokerFirm' => '001',  // Changed from 'TEST001' to '001' (3 chars max!)
        'ExitCDSAccount' => 'N',
        'CDSAccountNo' => '',
        'TinNo' => '123',
        'ResAddressStatus' => 'Y',
        'ResAddressStatusDesc' => '',
        'ResAddressLine01' => '123 St',
        'ResAddressLine02' => 'Area',
        'ResAddressLine03' => '',
        'ResAddressTown' => 'Col',
        // 🔴 District code might also have length limits
        'ResAddressDistrict' => '01',  // 2 chars
        'Country' => 'LK',  // 2 chars
        'CorrAddressStatus' => 'Y',
        'CorrAddressLine01' => '123 St',
        'CorrAddressLine02' => 'Area',
        'CorrAddressLine03' => '',
        'CorrAddressTown' => 'Col',
        'CorrAddressDistrict' => '01',
        'BankAccountNo' => '123456',
        // 🔴 Bank code likely also has length limits
        'BankCode' => '01',  // Changed from 'BOC' to '01' (2 chars)
        'BankBranch' => '001',
        'BankAccountType' => 'I',
        'EmployeStatus' => 'Y',
        'Occupation' => 'SE',
        'NameOfEmployer' => 'TC',
        'AddressOfEmployer' => '456 Rd',
        'OfficePhoneNo' => '011987654',
        'OfficeEmail' => 'o@tc.com',
        'EmployeeComment' => 'Test',
        'NameOfBusiness' => '',
        'AddressOfBusiness' => '',
        'OtherConnBusinessStatus' => 'N',
        'OtherConnBusinessDesc' => '',
        'ExpValueInvestment' => '2',
        'SourseOfFund' => '1',
        'UsaPersonStatus' => 'N',
        'UsaTaxIdentificationNo' => '',
        'FactaDeclaration' => 'N',
        'DualCitizenship' => 'N',
        'DualCitizenCountry' => '',
        'DualCitizenPassport' => '',
        'IsPEP' => 'N',
        'PepQ1' => 'N',
        'PepQ1Details' => '',
        'PepQ2' => 'N',
        'PepQ2Details' => '',
        'PepQ3' => 'N',
        'PepQ3Details' => '',
        'PepQ4' => 'N',
        'PepQ4Detailas' => '',
        'LatigationStatus' => 'N',
        'LatigationDetails' => '',
        'Status' => '1',
        'EnterUser' => 'SYS',  // Shorter
        'SaveTable' => 'U',
        'SignData' => '',
        // 🔴 This field caused the error! Must be 3 chars or less
        'StrockBrokerFirmName' => 'TST',  // Changed to 3 chars (was empty)
        'CountryOfResidency' => 'LK',
        'Nationality' => 'LK',
        'ClientType' => 'FI',
        'Residency' => 'R',
        'IsLKPassport' => 'N',
        'InvestorId' => '',
        'InvestmentOb' => 'CG',
        'InvestmentStrategy' => 'LT',
        'EnterDate' => date('Y/m/d'),
        'ApiUser' => 'DIA',  // Shorter
        'ApiRefNo' => $testApiRef
    ];
    
    // Log what we're sending
    echo "Sending test data with SHORT field values:\n";
    echo "- UserID: $testUserId (length: " . strlen($testUserId) . ")\n";
    echo "- BrokerFirm: '{$userData['BrokerFirm']}' (length: " . strlen($userData['BrokerFirm']) . ")\n";
    echo "- StrockBrokerFirmName: '{$userData['StrockBrokerFirmName']}' (length: " . strlen($userData['StrockBrokerFirmName']) . ")\n";
    
    // Call SaveUser endpoint
    $url = API_BASE_URL . '/api/User/SaveUser';
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($userData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);
    
    echo "\nCalling SaveUser endpoint...\n";
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Status Code: $httpCode\n";
    
    if ($curlError) {
        echo "cURL Error: $curlError\n";
    }
    
    if ($result) {
        echo "Response: $result\n";
        
        // Try to parse response
        if (is_numeric($result)) {
            echo "\n✅ SUCCESS! Account created with ID: $result\n";
            return $result;
        }
        
        $response = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Parsed JSON response:\n";
            print_r($response);
            
            if (is_numeric($response) || isset($response['AccountID'])) {
                $accountId = is_numeric($response) ? $response : $response['AccountID'];
                echo "\n✅ SUCCESS! Account created with ID: $accountId\n";
                return $accountId;
            } else {
                echo "\n⚠️ Response doesn't contain AccountID\n";
            }
        } else {
            echo "JSON Parse Error: " . json_last_error_msg() . "\n";
            echo "Raw response (first 500 chars): " . substr($result, 0, 500) . "\n";
        }
    } else {
        echo "No response received\n";
    }
    
    return null;
}

// ==================== MAIN EXECUTION ====================
echo "=== CSE API SaveUser Test ===\n";

// Step 1: Get token
$token = getAuthToken();

if ($token) {
    echo "✅ Authentication successful\n";
    echo "Token (first 50 chars): " . substr($token, 0, 50) . "...\n";
    
    // Step 2: Test SaveUser
    $accountId = testSaveUser($token);
    
    if ($accountId) {
        echo "\n🎉 Test completed successfully!\n";
        echo "Test Account ID: $accountId\n";
        echo "Remember to clean up test data if needed.\n";
    } else {
        echo "\n❌ SaveUser test failed\n";
    }
} else {
    echo "❌ Authentication failed\n";
}