<?php
/**
 * Email notifications – account creation & admin update.
 * Uses PHPMailer with SMTP. Requires config.php to be loaded.
 */
if (!defined('SMTP_HOST')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/** Keys to exclude from account creation email (system / internal) */
const EMAIL_EXCLUDE_KEYS = [
    'ApiRefNo', 'EnterDate', 'EnterUser', 'ApiUser', 'ClientType', 'Residency',
    'Status', 'accountId', 'account_id', 'UserID',
];

/** Keys containing these substrings are excluded (e.g. image paths, upload fields) */
const EMAIL_EXCLUDE_PATTERNS = ['upload', 'image', '_path'];

/**
 * Field labels for email display (matches admin edit fieldConfig).
 */
function get_email_field_labels() {
    static $labels = null;
    if ($labels !== null) return $labels;

    require_once dirname(__DIR__) . '/includes/form_constants.php';
    if (!function_exists('cse_getBrokersForDropdown')) {
        require_once dirname(__DIR__) . '/lib/cse_api.php';
    }
    $idProof = get_form_id_proof_options();
    $resAddr = get_form_res_address_status_options();
    $empl = get_form_employment_status_options();
    $expVal = get_form_exp_value_options();
    $sourceFunds = get_form_source_of_funds_options();

    $brokers = [];
    if (function_exists('cse_getBrokersForDropdown')) {
        $brokers = cse_getBrokersForDropdown();
    }
    $titles = [];
    if (function_exists('cse_getTitlesForDropdown')) {
        $titles = cse_getTitlesForDropdown();
    }

    $labels = [
        'Title' => 'Title',
        'Initials' => 'Initials',
        'Surname' => 'Surname',
        'NameDenoInitials' => 'Full Name (Denoted by Initials)',
        'MobileNo' => 'Mobile Number',
        'TelphoneNo' => 'Telephone Number',
        'Email' => 'Email Address',
        'DateOfBirthday' => 'Date of Birth',
        'Gender' => 'Gender',
        'IdentificationProof' => 'Identification Proof',
        'NicNo' => 'NIC No',
        'PassportNo' => 'Passport No',
        'PassportExpDate' => 'Passport Expiry Date',
        'BrokerFirm' => 'Stock Broker Firm',
        'ExitCDSAccount' => 'Existing CDS Account',
        'CDSAccountNo' => 'CDS Account Number',
        'TinNo' => 'TIN Number',
        'InvestorId' => 'Investor / Advisor',
        'InvestmentOb' => 'Investment Objectives',
        'InvestmentStrategy' => 'Investment Strategy',
        'ResAddressStatus' => 'Residential Address Status',
        'ResAddressStatusDesc' => 'Res Address Status Desc',
        'ResAddressLine01' => 'Address Line 1',
        'ResAddressLine02' => 'Address Line 2',
        'ResAddressLine03' => 'Address Line 3',
        'ResAddressTown' => 'Town',
        'ResAddressDistrict' => 'District',
        'Country' => 'Country',
        'CountryOfResidency' => 'Country of Residency',
        'Nationality' => 'Nationality',
        'CorrAddressStatus' => 'Correspondence Same as Residential?',
        'CorrAddressLine01' => 'Corr Address Line 1',
        'CorrAddressLine02' => 'Corr Address Line 2',
        'CorrAddressLine03' => 'Corr Address Line 3',
        'CorrAddressTown' => 'Corr Address Town',
        'CorrAddressDistrict' => 'Corr Address District',
        'EmployeStatus' => 'Employment Status',
        'Occupation' => 'Occupation',
        'NameOfEmployer' => 'Name of Employer',
        'AddressOfEmployer' => 'Address of Employer',
        'OfficePhoneNo' => 'Office Phone Number',
        'OfficeEmail' => 'Office Email',
        'EmployeeComment' => 'Employee Comment',
        'NameOfBusiness' => 'Name of Business',
        'AddressOfBusiness' => 'Address of Business',
        'OtherConnBusinessStatus' => 'Other Connected Business',
        'OtherConnBusinessDesc' => 'Other Connected Business Desc',
        'BankAccountNo' => 'Bank Account Number',
        'BankCode' => 'Bank Code',
        'BankBranch' => 'Bank Branch',
        'BankAccountType' => 'Bank Account Type',
        'ExpValueInvestment' => 'Expected Value of Investment',
        'SourseOfFund' => 'Source of Funds',
        'IsPEP' => 'Politically Exposed Person (PEP)',
        'PEP_Q1' => 'PEP Q1',
        'PEP_Q1_Details' => 'PEP Q1 Details',
        'PEP_Q2' => 'PEP Q2',
        'PEP_Q2_Details' => 'PEP Q2 Details',
        'PEP_Q3' => 'PEP Q3',
        'PEP_Q3_Details' => 'PEP Q3 Details',
        'PEP_Q4' => 'PEP Q4',
        'PEP_Q4_Details' => 'PEP Q4 Details',
        'LitigationStatus' => 'Litigation Status',
        'LitigationDetails' => 'Litigation Details',
        'UsaPersonStatus' => 'USA Person Status',
        'UsaTaxIdentificationNo' => 'USA Tax Identification No',
        'FactaDeclaration' => 'FATCA Declaration',
        'DualCitizenship' => 'Dual Citizenship',
        'DualCitizenCountry' => 'Dual Citizen Country',
        'DualCitizenPassport' => 'Dual Citizen Passport No',
        'IsLKPassport' => 'Is LK Passport?',
    ];

    $options = [
        'IdentificationProof' => $idProof,
        'ResAddressStatusDesc' => $resAddr,
        'EmployeStatus' => $empl,
        'ExpValueInvestment' => $expVal,
        'SourseOfFund' => $sourceFunds,
        'BrokerFirm' => $brokers,
        'Title' => $titles,
        'Gender' => ['M' => 'Male', 'F' => 'Female'],
        'ExitCDSAccount' => ['Y' => 'Yes', 'N' => 'No'],
        'ResAddressStatus' => ['Y' => 'Yes', 'N' => 'No'],
        'CorrAddressStatus' => ['Y' => 'Yes', 'N' => 'No'],
        'IsPEP' => ['Y' => 'Yes', 'N' => 'No'],
        'PEP_Q1' => ['Y' => 'Yes', 'N' => 'No'],
        'PEP_Q2' => ['Y' => 'Yes', 'N' => 'No'],
        'PEP_Q3' => ['Y' => 'Yes', 'N' => 'No'],
        'PEP_Q4' => ['Y' => 'Yes', 'N' => 'No'],
        'LitigationStatus' => ['Y' => 'Yes', 'N' => 'No'],
        'UsaPersonStatus' => ['Y' => 'Yes', 'N' => 'No'],
        'FactaDeclaration' => ['Y' => 'Yes', 'N' => 'No'],
        'DualCitizenship' => ['Y' => 'Yes', 'N' => 'No'],
        'OtherConnBusinessStatus' => ['Y' => 'Yes', 'N' => 'No'],
        'IsLKPassport' => ['Y' => 'Yes', 'N' => 'No'],
    ];

    $labels['_options'] = $options;
    return $labels;
}

function get_email_display_value($key, $value) {
    if ($value === '' || $value === null) return '';
    $labels = get_email_field_labels();
    $opts = $labels['_options'] ?? [];
    if (isset($opts[$key]) && isset($opts[$key][$value])) {
        return $opts[$key][$value];
    }
    return is_scalar($value) ? (string)$value : json_encode($value);
}

function getEmailHeader() {
    return '<div style="margin: 0; padding: 24px; font-family: Arial, sans-serif; background-color: #F9F9F9; color: #3D3D3D;">
	<div style="max-width: 550px; margin: 0 auto; background-color: #FFFFFF; border-radius: 8px; overflow: hidden; border: 1px solid #E7E7E7;">
		<!-- Header -->
		<div style="background-color: #000000; padding: 20px; text-align: center;">
			<img src="https://www.sampathsecurities.lk/wp-content/uploads/sampath-securities-logo-white.png" 
				 alt="Sampath Securities (Pvt) Limited" 
				 style="max-width: 200px; height: auto;">
		</div>
		<!-- Content -->
		<div style="padding: 24px;">';
}

function getEmailFooter() {
    return '			<!-- Footer -->
			<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #E7E7E7; text-align: center; color: #8E8E93; font-size: 12px;">
				<p style="margin-bottom: 5px;">This email was automatically generated from <a href="https://www.sampathsecurities.lk/" style="color: #FF8800; text-decoration: none;">sampathsecurities.lk</a></p>
				<p style="margin: 0;">Sampath Securities (Pvt) Ltd • 5th Floor, 26B, Alwis Place, Colombo 3, Sri Lanka.</p>
			</div>
		</div>
	</div>
</div>';
}

function email_log($message, $level = 'info') {
    $logFile = defined('CSE_LOG_FILE') && CSE_LOG_FILE ? CSE_LOG_FILE : (__DIR__ . '/../storage/api.log');
    $line = '[' . date('Y-m-d H:i:s') . '] [EMAIL][' . strtoupper($level) . '] ' . $message . PHP_EOL;
    if (is_dir(dirname($logFile))) {
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}

function createPhpMailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = (SMTP_ENCRYPTION === 'tls') ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->isHTML(true);
    $mail->setFrom(SMTP_USERNAME, 'Sampath Securities');
    return $mail;
}

function buildFormDataTableRows(array $formData): string {
    $labels = get_email_field_labels();
    unset($labels['_options']);
    $rows = '';
    foreach ($formData as $key => $value) {
        if (in_array($key, EMAIL_EXCLUDE_KEYS, true)) continue;
        foreach (EMAIL_EXCLUDE_PATTERNS as $pat) {
            if (stripos($key, $pat) !== false) continue 2;
        }
        if (is_array($value)) continue;
        $label = $labels[$key] ?? preg_replace('/([a-z])([A-Z])/', '$1 $2', $key);
        $displayVal = get_email_display_value($key, $value);
        $displayVal = htmlspecialchars($displayVal ?: '-');
        if ($key === 'Email') {
            $displayVal = '<a href="mailto:' . htmlspecialchars($value) . '" style="color: #DD4200; text-decoration: none;">' . $displayVal . '</a>';
        }
        $rows .= '<tr>
			<td style="padding: 12px 15px; border: 1px solid #E7E7E7; font-weight: bold; width: 35%; color: #3D3D3D;">' . htmlspecialchars($label) . '</td>
			<td style="padding: 12px 15px; border: 1px solid #E7E7E7; color: #000000;">' . $displayVal . '</td>
		</tr>';
    }
    return $rows;
}

function buildAccountCreationBody(array $formData, string $accountId) {
    $submissionDate = date('F j, Y \a\t g:i A');
    $rows = buildFormDataTableRows($formData);
    $header = getEmailHeader();
    $footer = getEmailFooter();
    $body = $header . '
			<h1 style="color: #FF8800; font-size: 24px; margin-bottom: 5px; border-bottom: 2px solid #E7E7E7; padding-bottom: 10px;">
				CDS Account Registration Confirmation
			</h1>
			<p style="color: #8E8E93; font-size: 14px; margin-bottom: 30px;">
				Submitted on: ' . htmlspecialchars($submissionDate) . '<br>Account ID: ' . htmlspecialchars($accountId) . '
			</p>
			<table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
				<tr style="background-color: #F9F9F9;">
					<td colspan="2" style="padding: 15px; color: #000000; font-weight: bold; border: 1px solid #E7E7E7;">
						Your Submitted Details
					</td>
				</tr>
				' . $rows . '
			</table>
			' . $footer;
    return $body;
}

function buildAccountUpdateBody(array $formData, string $accountId) {
    $rows = buildFormDataTableRows($formData);
    $header = getEmailHeader();
    $footer = getEmailFooter();
    $body = $header . '
			<h1 style="color: #FF8800; font-size: 24px; margin-bottom: 5px; border-bottom: 2px solid #E7E7E7; padding-bottom: 10px;">
				Your Account Details Have Been Updated
			</h1>
			<p style="color: #3D3D3D; font-size: 14px; margin-bottom: 30px;">
				Your CDS account (Account ID: ' . htmlspecialchars($accountId) . ') has been updated by our team.
			</p>
			<table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
				<tr style="background-color: #F9F9F9;">
					<td colspan="2" style="padding: 15px; color: #000000; font-weight: bold; border: 1px solid #E7E7E7;">
						Your Updated Details
					</td>
				</tr>
				' . $rows . '
			</table>
			<p style="color: #3D3D3D; font-size: 14px;">
				If you have any questions, please contact us.
			</p>
			' . $footer;
    return $body;
}

/**
 * Send account creation confirmation email with user-entered data.
 */
function sendAccountCreationEmail(array $formData, string $accountId) {
    $to = trim($formData['Email'] ?? '');
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        email_log('Account creation email skipped: invalid/missing recipient', 'warn');
        return false;
    }
    if (!SMTP_HOST || !SMTP_USERNAME || !SMTP_PASSWORD) {
        email_log('Account creation email skipped: SMTP not configured', 'warn');
        return false;
    }
    try {
        $mail = createPhpMailer();
        $mail->addAddress($to);
        $mail->Subject = 'CDS Account Registration Confirmation - ' . $accountId;
        $mail->Body = buildAccountCreationBody($formData, $accountId);
        $mail->send();
        email_log('Account creation email sent to ' . $to . ' for account ' . $accountId);
        return true;
    } catch (PHPMailerException $e) {
        email_log('Account creation email failed: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Send notification when admin updates user details.
 */
function sendAccountUpdateEmail(array $formData, string $accountId) {
    $to = trim($formData['Email'] ?? '');
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        email_log('Account update email skipped: invalid/missing recipient', 'warn');
        return false;
    }
    if (!SMTP_HOST || !SMTP_USERNAME || !SMTP_PASSWORD) {
        email_log('Account update email skipped: SMTP not configured', 'warn');
        return false;
    }
    try {
        $mail = createPhpMailer();
        $mail->addAddress($to);
        $mail->Subject = 'Your CDS Account Details Have Been Updated - ' . $accountId;
        $mail->Body = buildAccountUpdateBody($formData, $accountId);
        $mail->send();
        email_log('Account update email sent to ' . $to . ' for account ' . $accountId);
        return true;
    } catch (PHPMailerException $e) {
        email_log('Account update email failed: ' . $e->getMessage(), 'error');
        return false;
    }
}
