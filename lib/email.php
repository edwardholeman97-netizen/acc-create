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
        'NameDenoInitials' => 'Name Denoted by Initials',
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

/**
 * Send admin password reset link (HTML).
 */
function sendAdminPasswordResetEmail(string $toEmail, string $resetUrl): bool {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        email_log('Password reset email skipped: invalid recipient', 'warn');
        return false;
    }
    if (!SMTP_HOST || !SMTP_USERNAME || !SMTP_PASSWORD) {
        email_log('Password reset email skipped: SMTP not configured', 'warn');
        return false;
    }
    $expiryMins = defined('ADMIN_PASSWORD_RESET_EXPIRY_SECONDS') ? (int) (ADMIN_PASSWORD_RESET_EXPIRY_SECONDS / 60) : 60;
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $header = getEmailHeader();
    $footer = getEmailFooter();
    $body = $header . '
			<h1 style="color: #FF8800; font-size: 24px; margin-bottom: 5px; border-bottom: 2px solid #E7E7E7; padding-bottom: 10px;">
				Reset your CDS admin password
			</h1>
			<p style="color: #3D3D3D; font-size: 14px; margin-bottom: 20px;">
				We received a request to reset the password for this email address. Click the button below to choose a new password.
			</p>
			<div style="text-align: center; margin: 28px 0;">
				<a href="' . $safeUrl . '" style="display: inline-block; background-color: #DD4200; padding: 14px 24px; border-radius: 8px; color: #FFFFFF; text-decoration: none; font-weight: 600;">Reset password</a>
			</div>
			<p style="color: #8E8E93; font-size: 13px; margin-bottom: 16px;">
				This link expires in about ' . (int) $expiryMins . ' minutes. If you did not request a reset, you can ignore this email.
			</p>
			<p style="color: #8E8E93; font-size: 12px; word-break: break-all;">
				If the button does not work, copy and paste this URL into your browser:<br>
				<a href="' . $safeUrl . '" style="color: #FF8800;">' . $safeUrl . '</a>
			</p>
			' . $footer;
    try {
        $mail = createPhpMailer();
        $mail->addAddress($toEmail);
        $mail->Subject = 'CDS Admin — password reset';
        $mail->Body = $body;
        $mail->send();
        email_log('Password reset email sent to ' . $toEmail);
        return true;
    } catch (PHPMailerException $e) {
        email_log('Password reset email failed: ' . $e->getMessage(), 'error');
        return false;
    }
}

function email_table_key_excluded(string $key): bool {
    if (in_array($key, EMAIL_EXCLUDE_KEYS, true)) return true;
    foreach (EMAIL_EXCLUDE_PATTERNS as $pat) {
        if (stripos($key, $pat) !== false) return true;
    }
    return false;
}

function email_field_excluded_from_table(string $key, $value): bool {
    if (email_table_key_excluded($key)) return true;
    if (is_array($value)) return true;
    return false;
}

function buildFormDataTableRows(array $formData): string {
    $labels = get_email_field_labels();
    unset($labels['_options']);
    $rows = '';
    foreach ($formData as $key => $value) {
        if (email_field_excluded_from_table($key, $value)) continue;
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

/**
 * HTML table rows (3 columns: Field, Previous, New) for scalar fields that changed between $before and $after.
 */
function buildFormDataDiffTableRows(array $before, array $after): string {
    $labels = get_email_field_labels();
    unset($labels['_options']);
    $keys = array_unique(array_merge(array_keys($after), array_keys($before)));
    sort($keys);
    $rows = '';
    foreach ($keys as $key) {
        if (email_table_key_excluded($key)) {
            continue;
        }
        $oldRaw = $before[$key] ?? '';
        $newRaw = $after[$key] ?? '';
        if (is_array($oldRaw) || is_array($newRaw)) {
            continue;
        }
        $oldNorm = trim((string)$oldRaw);
        $newNorm = trim((string)$newRaw);
        if ($oldNorm === $newNorm) {
            continue;
        }
        $label = $labels[$key] ?? preg_replace('/([a-z])([A-Z])/', '$1 $2', $key);
        $prevDisp = get_email_display_value($key, $oldRaw);
        $newDisp = get_email_display_value($key, $newRaw);
        $prevHtml = htmlspecialchars($prevDisp !== '' ? $prevDisp : '—');
        $newHtml = htmlspecialchars($newDisp !== '' ? $newDisp : '—');
        if ($key === 'Email') {
            if ($oldNorm !== '') {
                $prevHtml = '<a href="mailto:' . htmlspecialchars($oldRaw) . '" style="color: #DD4200; text-decoration: none;">' . $prevHtml . '</a>';
            }
            if ($newNorm !== '') {
                $newHtml = '<a href="mailto:' . htmlspecialchars($newRaw) . '" style="color: #DD4200; text-decoration: none;">' . $newHtml . '</a>';
            }
        }
        $rows .= '<tr>
			<td style="padding: 12px 15px; border: 1px solid #E7E7E7; font-weight: bold; width: 28%; color: #3D3D3D;">' . htmlspecialchars($label) . '</td>
			<td style="padding: 12px 15px; border: 1px solid #E7E7E7; color: #666666;">' . $prevHtml . '</td>
			<td style="padding: 12px 15px; border: 1px solid #E7E7E7; color: #000000;">' . $newHtml . '</td>
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

function buildAccountUpdateTableBlock(array $formData, ?array $previousFormData): string {
    if ($previousFormData !== null) {
        $diffRows = buildFormDataDiffTableRows($previousFormData, $formData);
        if ($diffRows === '') {
            return '<p style="color: #3D3D3D; font-size: 14px; margin-bottom: 24px;">No form field values were changed in this update (for example, only documents may have been updated).</p>';
        }
        return '<table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
				<tr style="background-color: #F9F9F9;">
					<td colspan="3" style="padding: 15px; color: #000000; font-weight: bold; border: 1px solid #E7E7E7;">
						Details that changed
					</td>
				</tr>
				<tr style="background-color: #F9F9F9;">
					<td style="padding: 10px 15px; border: 1px solid #E7E7E7; font-weight: bold; font-size: 12px; color: #3D3D3D;">Field</td>
					<td style="padding: 10px 15px; border: 1px solid #E7E7E7; font-weight: bold; font-size: 12px; color: #3D3D3D;">Previous</td>
					<td style="padding: 10px 15px; border: 1px solid #E7E7E7; font-weight: bold; font-size: 12px; color: #3D3D3D;">New</td>
				</tr>
				' . $diffRows . '
			</table>';
    }
    $rows = buildFormDataTableRows($formData);
    return '<table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
				<tr style="background-color: #F9F9F9;">
					<td colspan="2" style="padding: 15px; color: #000000; font-weight: bold; border: 1px solid #E7E7E7;">
						Your Updated Details
					</td>
				</tr>
				' . $rows . '
			</table>';
}

function buildAccountUpdateBody(array $formData, string $accountId, ?array $previousFormData = null) {
    $header = getEmailHeader();
    $footer = getEmailFooter();
    $tableBlock = buildAccountUpdateTableBlock($formData, $previousFormData);
    $body = $header . '
			<h1 style="color: #FF8800; font-size: 24px; margin-bottom: 5px; border-bottom: 2px solid #E7E7E7; padding-bottom: 10px;">
				Your Account Details Have Been Updated
			</h1>
			<p style="color: #3D3D3D; font-size: 14px; margin-bottom: 30px;">
				Your CDS account (Account ID: ' . htmlspecialchars($accountId) . ') has been updated by our team.
			</p>
			' . $tableBlock . '
			<p style="color: #3D3D3D; font-size: 14px;">
				If you have any questions, please contact us.
			</p>
			' . $footer;
    return $body;
}

function parse_admin_notify_emails(): array {
    if (!defined('ADMIN_NOTIFY_EMAIL') || trim((string)ADMIN_NOTIFY_EMAIL) === '') {
        return [];
    }
    $out = [];
    foreach (explode(',', ADMIN_NOTIFY_EMAIL) as $part) {
        $e = trim($part);
        if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
            $out[] = $e;
        }
    }
    return array_values(array_unique($out));
}

function buildAdminAccountUpdateNotifyBody(array $formData, string $accountId, ?array $previousFormData, ?string $updatedByAdminEmail): string {
    $header = getEmailHeader();
    $footer = getEmailFooter();
    $tableBlock = buildAccountUpdateTableBlock($formData, $previousFormData);
    $when = date('F j, Y \a\t g:i A');
    $userEmail = htmlspecialchars(trim($formData['Email'] ?? ''));
    $nameRaw = trim($formData['NameDenoInitials'] ?? '');
    if ($nameRaw === '') {
        $nameRaw = trim(($formData['Initials'] ?? '') . ' ' . ($formData['Surname'] ?? ''));
    }
    $userName = htmlspecialchars($nameRaw !== '' ? $nameRaw : '—');
    $byLine = '';
    if ($updatedByAdminEmail !== null && $updatedByAdminEmail !== '') {
        $byLine = '<p style="color: #3D3D3D; font-size: 14px; margin-bottom: 8px;">Updated by: <strong>' . htmlspecialchars($updatedByAdminEmail) . '</strong></p>';
    }
    $body = $header . '
			<h1 style="color: #FF8800; font-size: 24px; margin-bottom: 5px; border-bottom: 2px solid #E7E7E7; padding-bottom: 10px;">
				User details updated successfully
			</h1>
			<p style="color: #3D3D3D; font-size: 14px; margin-bottom: 16px;">
				A CDS submission was saved and synced to CSE successfully.
			</p>
			<p style="color: #3D3D3D; font-size: 14px; margin-bottom: 6px;"><strong>Account ID:</strong> ' . htmlspecialchars($accountId) . '</p>
			<p style="color: #3D3D3D; font-size: 14px; margin-bottom: 6px;"><strong>User email:</strong> ' . $userEmail . '</p>
			<p style="color: #3D3D3D; font-size: 14px; margin-bottom: 6px;"><strong>User name:</strong> ' . $userName . '</p>
			<p style="color: #8E8E93; font-size: 13px; margin-bottom: 20px;">' . htmlspecialchars($when) . '</p>
			' . $byLine . '
			' . $tableBlock . '
			<p style="color: #3D3D3D; font-size: 14px;">
				This is an automated notification from the CDS admin system.
			</p>
			' . $footer;
    return $body;
}

/**
 * Notify configured admin addresses that a submission was updated successfully.
 */
function sendAdminAccountUpdateNotifyEmail(array $formData, string $accountId, ?array $previousFormData = null, ?string $updatedByAdminEmail = null): bool {
    $recipients = parse_admin_notify_emails();
    if (empty($recipients)) {
        return false;
    }
    if (!SMTP_HOST || !SMTP_USERNAME || !SMTP_PASSWORD) {
        email_log('Admin notify email skipped: SMTP not configured', 'warn');
        return false;
    }
    try {
        $mail = createPhpMailer();
        foreach ($recipients as $addr) {
            $mail->addAddress($addr);
        }
        $mail->Subject = '[CDS Admin] User updated successfully — Account ' . $accountId;
        $mail->Body = buildAdminAccountUpdateNotifyBody($formData, $accountId, $previousFormData, $updatedByAdminEmail);
        $mail->send();
        email_log('Admin update notify sent to ' . implode(', ', $recipients) . ' for account ' . $accountId);
        return true;
    } catch (PHPMailerException $e) {
        email_log('Admin update notify failed: ' . $e->getMessage(), 'error');
        return false;
    }
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
function sendAccountUpdateEmail(array $formData, string $accountId, ?array $previousFormData = null) {
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
        $mail->Body = buildAccountUpdateBody($formData, $accountId, $previousFormData);
        $mail->send();
        email_log('Account update email sent to ' . $to . ' for account ' . $accountId);
        return true;
    } catch (PHPMailerException $e) {
        email_log('Account update email failed: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Send a "we received your application — pending admin review" email after the
 * client first submits the form. The submission is in the DB only, not yet at CSE.
 */
function sendClientPendingReviewEmail(array $formData, string $submissionUid): bool {
    $to = trim($formData['Email'] ?? '');
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        email_log('Pending review email skipped: invalid/missing recipient', 'warn');
        return false;
    }
    if (!SMTP_HOST || !SMTP_USERNAME || !SMTP_PASSWORD) {
        email_log('Pending review email skipped: SMTP not configured', 'warn');
        return false;
    }
    $name = htmlspecialchars(trim(($formData['Initials'] ?? '') . ' ' . ($formData['Surname'] ?? ''))) ?: 'Customer';
    $header = getEmailHeader();
    $footer = getEmailFooter();
    $body = $header . '
        <h1 style="color: #FF8800; font-size: 24px; margin-bottom: 5px; border-bottom: 2px solid #E7E7E7; padding-bottom: 10px;">
            We received your CDS application
        </h1>
        <p style="color: #3D3D3D; font-size: 14px; margin-bottom: 16px;">
            Hi ' . $name . ',
        </p>
        <p style="color: #3D3D3D; font-size: 14px; margin-bottom: 16px;">
            Thank you for submitting your CDS Account Opening application. It is now with our review team.
            We will email you again once your account has been approved and submitted to CSE, or if any
            changes are required.
        </p>
        <p style="color: #8E8E93; font-size: 13px; margin-bottom: 0;">
            Reference: <strong>' . htmlspecialchars($submissionUid) . '</strong>
        </p>
        ' . $footer;
    try {
        $mail = createPhpMailer();
        $mail->addAddress($to);
        $mail->Subject = 'CDS Application Received - Pending Review';
        $mail->Body = $body;
        $mail->send();
        email_log('Pending review email sent to ' . $to . ' (uid=' . $submissionUid . ')');
        return true;
    } catch (PHPMailerException $e) {
        email_log('Pending review email failed: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Send a client edit link with optional admin note. Clicking the link opens
 * a prefilled form; submitting it pushes directly to CSE.
 */
function sendClientEditLinkEmail(array $formData, string $editUrl, ?string $note = null): bool {
    $to = trim($formData['Email'] ?? '');
    if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        email_log('Edit link email skipped: invalid/missing recipient', 'warn');
        return false;
    }
    if (!SMTP_HOST || !SMTP_USERNAME || !SMTP_PASSWORD) {
        email_log('Edit link email skipped: SMTP not configured', 'warn');
        return false;
    }
    $name = htmlspecialchars(trim(($formData['Initials'] ?? '') . ' ' . ($formData['Surname'] ?? ''))) ?: 'Customer';
    $safeUrl = htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8');
    $noteHtml = '';
    if ($note !== null && trim($note) !== '') {
        $noteHtml = '
            <div style="background: #FFF8E6; border-left: 4px solid #FF8800; padding: 14px 18px; margin: 16px 0; border-radius: 4px;">
                <p style="margin: 0 0 6px; color: #3D3D3D; font-weight: 600;">Note from our review team:</p>
                <p style="margin: 0; color: #3D3D3D; white-space: pre-wrap;">' . nl2br(htmlspecialchars($note)) . '</p>
            </div>';
    }
    $header = getEmailHeader();
    $footer = getEmailFooter();
    $body = $header . '
        <h1 style="color: #FF8800; font-size: 24px; margin-bottom: 5px; border-bottom: 2px solid #E7E7E7; padding-bottom: 10px;">
            Action required: review your CDS application
        </h1>
        <p style="color: #3D3D3D; font-size: 14px; margin-bottom: 16px;">
            Hi ' . $name . ',
        </p>
        <p style="color: #3D3D3D; font-size: 14px; margin-bottom: 16px;">
            Our review team has asked you to review and update your CDS Account Opening application.
            Click the button below to open your application.
        </p>
        ' . $noteHtml . '
        <div style="text-align: center; margin: 28px 0;">
            <a href="' . $safeUrl . '" style="display: inline-block; background-color: #DD4200; padding: 14px 24px; border-radius: 8px; color: #FFFFFF; text-decoration: none; font-weight: 600;">Open my application</a>
        </div>
        <p style="color: #8E8E93; font-size: 13px; margin-bottom: 16px;">
            For your security, this link expires in 3 days, or as soon as you re-submit your application —
            whichever comes first.
        </p>
        <p style="color: #8E8E93; font-size: 12px; word-break: break-all;">
            If the button does not work, copy and paste this URL into your browser:<br>
            <a href="' . $safeUrl . '" style="color: #FF8800;">' . $safeUrl . '</a>
        </p>
        ' . $footer;
    try {
        $mail = createPhpMailer();
        $mail->addAddress($to);
        $mail->Subject = 'Action required: please review your CDS application';
        $mail->Body = $body;
        $mail->send();
        email_log('Edit link email sent to ' . $to);
        return true;
    } catch (PHPMailerException $e) {
        email_log('Edit link email failed: ' . $e->getMessage(), 'error');
        return false;
    }
}
