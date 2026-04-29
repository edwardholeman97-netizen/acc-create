<?php
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/connection.php';
require_once dirname(__DIR__) . '/lib/supporting_docs.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare(
    'SELECT id, submission_uid, account_id, cse_account_id, form_data, image_paths,'
    . ' supporting_documents, status, admin_note, submitted_to_cse_at, created_at, updated_at'
    . ' FROM cds_submissions WHERE id = ?'
);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: dashboard.php?msg=' . urlencode('Record not found.'));
    exit;
}

$submissionStatus = $row['status'] ?: 'submitted_to_cse';
$isLockedSubmission = ($submissionStatus === 'submitted_to_cse');
$isPendingSubmission = in_array($submissionStatus, ['pending_review', 'awaiting_edit'], true);
$isSuperadmin = admin_is_super();
// `?view=1` opens any record in read-only mode, even pending ones, so an admin
// can inspect a submission (incl. supporting docs) without committing edits.
$forceViewMode = !empty($_GET['view']);
// Superadmins bypass the post-CSE-submission read-only lockdown.
$effectiveLocked = ($isLockedSubmission && !$isSuperadmin) || $forceViewMode;

$formData = json_decode($row['form_data'], true) ?: [];
$imagePaths = json_decode($row['image_paths'] ?? '{}', true) ?: [];
$supportingDocs = supporting_docs_normalize($row['supporting_documents'] ?? null);

// Image folder identifier: prefer submission_uid (new rows), fall back to account_id
// (legacy rows whose folder was created before the migration).
$imageFolderId = $row['submission_uid'] ?: ($row['account_id'] ?: ('legacy-' . (int)$row['id']));
$displayCseId = $row['cse_account_id'] ?: ($row['account_id'] ?: '—');

require_once dirname(__DIR__) . '/lib/cse_api.php';
require_once dirname(__DIR__) . '/includes/form_constants.php';
$titlesOptions = cse_getTitlesForDropdown();
$brokersOptions = cse_getBrokersForDropdown();

// Field config: section => [fields]. Each field: key, label, type (text|email|tel|date|textarea|yn|select)
$fieldConfig = [
    'Personal Information' => [
        ['key' => 'Title', 'label' => 'Title', 'type' => 'select', 'options' => $titlesOptions],
        ['key' => 'Initials', 'label' => 'Initials', 'type' => 'text'],
        ['key' => 'Surname', 'label' => 'Surname', 'type' => 'text'],
        ['key' => 'NameDenoInitials', 'label' => 'Full Name (Denoted by Initials)', 'type' => 'text'],
        ['key' => 'MobileNo', 'label' => 'Mobile Number', 'type' => 'tel', 'locked' => true],
        ['key' => 'TelphoneNo', 'label' => 'Telephone Number', 'type' => 'tel', 'locked' => true],
        ['key' => 'Email', 'label' => 'Email Address', 'type' => 'email', 'locked' => true],
        ['key' => 'DateOfBirthday', 'label' => 'Date of Birth', 'type' => 'date'],
        ['key' => 'Gender', 'label' => 'Gender', 'type' => 'yn', 'options' => ['M' => 'Male', 'F' => 'Female']],
    ],
    'Identification' => [
        ['key' => 'IdentificationProof', 'label' => 'Identification Proof', 'type' => 'select', 'options' => get_form_id_proof_options()],
        ['key' => 'NicNo', 'label' => 'NIC No', 'type' => 'text', 'wrapperId' => 'nic-field', 'showWhen' => ['IdentificationProof' => 'NIC'], 'locked' => true],
        ['key' => 'PassportNo', 'label' => 'Passport No', 'type' => 'text', 'wrapperId' => 'passport-field', 'showWhen' => ['IdentificationProof' => 'Passport'], 'locked' => true],
        ['key' => 'PassportExpDate', 'label' => 'Passport Expiry Date', 'type' => 'date', 'wrapperId' => 'passport-exp-field', 'showWhen' => ['IdentificationProof' => 'Passport']],
    ],
    'Investment' => [
        ['key' => 'BrokerFirm', 'label' => 'Stock Broker Firm', 'type' => 'select', 'options' => $brokersOptions],
        ['key' => 'ExitCDSAccount', 'label' => 'Existing CDS Account', 'type' => 'yn'],
        ['key' => 'CDSAccountNo', 'label' => 'CDS Account Number', 'type' => 'text', 'wrapperId' => 'cds-account-field', 'showWhen' => ['ExitCDSAccount' => 'Y']],
        ['key' => 'TinNo', 'label' => 'TIN Number', 'type' => 'text'],
        ['key' => 'InvestorId', 'label' => 'Investor / Advisor', 'type' => 'text', 'locked' => true],
        ['key' => 'InvestmentOb', 'label' => 'Investment Objectives', 'type' => 'textarea'],
        ['key' => 'InvestmentStrategy', 'label' => 'Investment Strategy', 'type' => 'textarea'],
    ],
    'Address' => [
        ['key' => 'ResAddressStatus', 'label' => 'Residential Address Status', 'type' => 'yn'],
        ['key' => 'ResAddressStatusDesc', 'label' => 'Res Address Status Desc', 'type' => 'select', 'options' => get_form_res_address_status_options()],
        ['key' => 'ResAddressLine01', 'label' => 'Address Line 1', 'type' => 'text'],
        ['key' => 'ResAddressLine02', 'label' => 'Address Line 2', 'type' => 'text'],
        ['key' => 'ResAddressLine03', 'label' => 'Address Line 3', 'type' => 'text'],
        ['key' => 'ResAddressTown', 'label' => 'Town', 'type' => 'text'],
        ['key' => 'ResAddressDistrict', 'label' => 'District', 'type' => 'text'],
        ['key' => 'Country', 'label' => 'Country', 'type' => 'text'],
        ['key' => 'CountryOfResidency', 'label' => 'Country of Residency', 'type' => 'text'],
        ['key' => 'Nationality', 'label' => 'Nationality', 'type' => 'text'],
        ['key' => 'CorrAddressStatus', 'label' => 'Correspondence Same as Residential?', 'type' => 'yn'],
        ['key' => 'CorrAddressLine01', 'label' => 'Corr Address Line 1', 'type' => 'text'],
        ['key' => 'CorrAddressLine02', 'label' => 'Corr Address Line 2', 'type' => 'text'],
        ['key' => 'CorrAddressLine03', 'label' => 'Corr Address Line 3', 'type' => 'text'],
        ['key' => 'CorrAddressTown', 'label' => 'Corr Address Town', 'type' => 'text'],
        ['key' => 'CorrAddressDistrict', 'label' => 'Corr Address District', 'type' => 'text'],
    ],
    'Employment' => [
        ['key' => 'EmployeStatus', 'label' => 'Employment Status', 'type' => 'select', 'options' => get_form_employment_status_options()],
        ['key' => 'Occupation', 'label' => 'Occupation', 'type' => 'text', 'inGroup' => 'employment-details', 'groupShowWhen' => ['EmployeStatus' => ['Y', 'S']]],
        ['key' => 'NameOfEmployer', 'label' => 'Name of Employer', 'type' => 'text', 'inGroup' => 'employment-details'],
        ['key' => 'AddressOfEmployer', 'label' => 'Address of Employer', 'type' => 'text', 'inGroup' => 'employment-details'],
        ['key' => 'OfficePhoneNo', 'label' => 'Office Phone Number', 'type' => 'tel', 'inGroup' => 'employment-details'],
        ['key' => 'OfficeEmail', 'label' => 'Office Email', 'type' => 'email', 'inGroup' => 'employment-details'],
        ['key' => 'EmployeeComment', 'label' => 'Employee Comment', 'type' => 'textarea', 'inGroup' => 'employment-details'],
        ['key' => 'NameOfBusiness', 'label' => 'Name of Business', 'type' => 'text', 'inGroup' => 'employment-details'],
        ['key' => 'AddressOfBusiness', 'label' => 'Address of Business', 'type' => 'text', 'inGroup' => 'employment-details'],
        ['key' => 'OtherConnBusinessStatus', 'label' => 'Other Connected Business', 'type' => 'yn', 'inGroup' => 'employment-details'],
        ['key' => 'OtherConnBusinessDesc', 'label' => 'Other Connected Business Desc', 'type' => 'text', 'inGroup' => 'employment-details'],
    ],
    'Bank & Funds' => [
        ['key' => 'BankAccountNo', 'label' => 'Bank Account Number', 'type' => 'text', 'locked' => true],
        ['key' => 'BankCode', 'label' => 'Bank Code', 'type' => 'text', 'locked' => true],
        ['key' => 'BankBranch', 'label' => 'Bank Branch', 'type' => 'text', 'locked' => true],
        ['key' => 'BankAccountType', 'label' => 'Bank Account Type', 'type' => 'text', 'locked' => true],
        ['key' => 'ExpValueInvestment', 'label' => 'Expected Value of Investment', 'type' => 'select', 'options' => get_form_exp_value_options()],
        ['key' => 'SourseOfFund', 'label' => 'Source of Funds', 'type' => 'select', 'options' => get_form_source_of_funds_options()],
    ],
    'Compliance' => [
        ['key' => 'IsPEP', 'label' => 'Politically Exposed Person (PEP)', 'type' => 'yn'],
        ['key' => 'PEP_Q1', 'label' => 'PEP Q1', 'type' => 'yn', 'inGroup' => 'pep-questions', 'groupShowWhen' => ['IsPEP' => 'Y']],
        ['key' => 'PEP_Q1_Details', 'label' => 'PEP Q1 Details', 'type' => 'text', 'inGroup' => 'pep-questions'],
        ['key' => 'PEP_Q2', 'label' => 'PEP Q2', 'type' => 'yn', 'inGroup' => 'pep-questions'],
        ['key' => 'PEP_Q2_Details', 'label' => 'PEP Q2 Details', 'type' => 'text', 'inGroup' => 'pep-questions'],
        ['key' => 'PEP_Q3', 'label' => 'PEP Q3', 'type' => 'yn', 'inGroup' => 'pep-questions'],
        ['key' => 'PEP_Q3_Details', 'label' => 'PEP Q3 Details', 'type' => 'text', 'inGroup' => 'pep-questions'],
        ['key' => 'PEP_Q4', 'label' => 'PEP Q4', 'type' => 'yn', 'inGroup' => 'pep-questions'],
        ['key' => 'PEP_Q4_Details', 'label' => 'PEP Q4 Details', 'type' => 'text', 'inGroup' => 'pep-questions'],
        ['key' => 'LitigationStatus', 'label' => 'Litigation Status', 'type' => 'yn'],
        ['key' => 'LitigationDetails', 'label' => 'Litigation Details', 'type' => 'textarea', 'inGroup' => 'litigation-details', 'groupShowWhen' => ['LitigationStatus' => 'Y']],
        ['key' => 'UsaPersonStatus', 'label' => 'USA Person Status', 'type' => 'yn'],
        ['key' => 'UsaTaxIdentificationNo', 'label' => 'USA Tax Identification No', 'type' => 'text'],
        ['key' => 'FactaDeclaration', 'label' => 'FATCA Declaration', 'type' => 'yn'],
        ['key' => 'DualCitizenship', 'label' => 'Dual Citizenship', 'type' => 'yn'],
        ['key' => 'DualCitizenCountry', 'label' => 'Dual Citizen Country', 'type' => 'text'],
        ['key' => 'DualCitizenPassport', 'label' => 'Dual Citizen Passport No', 'type' => 'text'],
        ['key' => 'IsLKPassport', 'label' => 'Is LK Passport?', 'type' => 'yn'],
    ],
    'Other' => [], // Populated with any keys not in config
];

// Superadmins can edit every field, including the ones flagged as `locked`
// (phone, email, NIC/passport, bank details, investor id, …).
if ($isSuperadmin) {
    foreach ($fieldConfig as $section => &$_fields) {
        foreach ($_fields as &$_f) {
            unset($_f['locked']);
        }
        unset($_f);
    }
    unset($_fields);
}

$usedKeys = [];
$lockedKeys = [];
foreach ($fieldConfig as $section => $fields) {
    foreach ($fields as $f) {
        $usedKeys[$f['key']] = true;
        if (!empty($f['locked'])) {
            $lockedKeys[$f['key']] = true;
        }
    }
}
foreach (array_keys($formData) as $key) {
    if (!isset($usedKeys[$key])) {
        $fieldConfig['Other'][] = ['key' => $key, 'label' => preg_replace('/([a-z])([A-Z])/', '$1 $2', $key), 'type' => 'text'];
    }
}

// Remove empty Other section
if (empty($fieldConfig['Other'])) {
    unset($fieldConfig['Other']);
}

$sectionIcons = [
    'Personal Information' => 'fa-user',
    'Identification' => 'fa-id-card',
    'Investment' => 'fa-chart-line',
    'Address' => 'fa-home',
    'Employment' => 'fa-briefcase',
    'Bank & Funds' => 'fa-university',
    'Compliance' => 'fa-shield-alt',
    'Other' => 'fa-cog',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($effectiveLocked) {
        $msg = $forceViewMode
            ? 'You opened this record in view mode. Reopen it via Edit to make changes.'
            : 'This record has been submitted to CSE and is locked.';
        header('Location: dashboard.php?err=' . urlencode($msg));
        exit;
    }

    $updated = [];
    foreach ($formData as $k => $v) {
        if (isset($lockedKeys[$k])) {
            $updated[$k] = $v;
            continue;
        }
        $updated[$k] = $_POST['f_' . $k] ?? $v;
    }
    foreach ($_POST as $pk => $pv) {
        if (strpos($pk, 'f_') === 0) {
            $key = substr($pk, 2);
            if (isset($lockedKeys[$key])) {
                continue;
            }
            if (!isset($formData[$key])) {
                $updated[$key] = $pv;
            }
        }
    }

    $updated['ClientType'] = $updated['ClientType'] ?? 'FI';
    $updated['Residency'] = $updated['Residency'] ?? 'R';
    $updated['ApiUser'] = $updated['ApiUser'] ?? 'DIALOG';

    // Save any newly uploaded images (merge with existing). Folder uses submission_uid
    // for new rows and account_id for legacy rows.
    $newPaths = [];
    $fileMap = ['selfie_upload' => 'selfie', 'nic_front_upload' => 'nic_front', 'nic_back_upload' => 'nic_back', 'passport_upload' => 'passport'];
    $folderName = preg_replace('/[^a-zA-Z0-9_-]/', '', $imageFolderId);
    $baseDir = rtrim(CSE_STORAGE_PATH, '/') . '/uploads/' . $folderName;
    foreach ($fileMap as $fileKey => $baseName) {
        if (!empty($_FILES[$fileKey]['tmp_name']) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($baseDir)) @mkdir($baseDir, 0750, true);
            $ext = pathinfo($_FILES[$fileKey]['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg';
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png'], true)) $ext = 'jpg';
            $dest = $baseDir . '/' . $baseName . '.' . $ext;
            if (@copy($_FILES[$fileKey]['tmp_name'], $dest)) {
                $newPaths[$baseName] = 'uploads/' . $folderName . '/' . basename($dest);
            }
        }
    }
    if (!empty($newPaths)) {
        $imagePaths = array_merge($imagePaths, $newPaths);
    }

    // Apply supporting-doc adds/removes (server-only — never sent to CSE).
    $supportingMeta = $_POST['supporting_meta'] ?? null;
    $supportingRemoveIds = isset($_POST['supporting_remove']) && is_array($_POST['supporting_remove'])
        ? $_POST['supporting_remove'] : [];
    $supportingDocs = supporting_docs_apply_request(
        (string)$imageFolderId,
        $supportingDocs,
        $supportingMeta,
        $_FILES,
        $supportingRemoveIds
    );

    if ($isPendingSubmission) {
        // Pre-CSE state: admin is just cleaning data before approval. DB-only save.
        $stmt = $pdo->prepare('UPDATE cds_submissions SET form_data = ?, image_paths = ?, supporting_documents = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([
            json_encode($updated, JSON_UNESCAPED_UNICODE),
            json_encode($imagePaths, JSON_UNESCAPED_UNICODE),
            json_encode($supportingDocs, JSON_UNESCAPED_UNICODE),
            $id,
        ]);
        header('Location: dashboard.php?msg=' . urlencode('Pending submission updated.'));
        exit;
    }

    // Legacy / already-submitted-to-CSE rows: resubmit to CSE as before.
    require_once dirname(__DIR__) . '/lib/cse_api.php';
    $resubmitResult = cse_resubmitToApi($updated, $row['account_id'], $imagePaths);

    if (!$resubmitResult['success']) {
        header('Location: edit.php?id=' . $id . '&error=' . urlencode($resubmitResult['message']));
        exit;
    }

    $stmt = $pdo->prepare('UPDATE cds_submissions SET form_data = ?, image_paths = ?, supporting_documents = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([
        json_encode($updated, JSON_UNESCAPED_UNICODE),
        json_encode($imagePaths, JSON_UNESCAPED_UNICODE),
        json_encode($supportingDocs, JSON_UNESCAPED_UNICODE),
        $id,
    ]);

    require_once dirname(__DIR__) . '/lib/email.php';
    try {
        sendAccountUpdateEmail($updated, $row['account_id'], $formData);
    } catch (Throwable $e) {
        if (function_exists('email_log')) {
            email_log('Account update email failed: ' . $e->getMessage(), 'error');
        }
    }
    try {
        sendAdminAccountUpdateNotifyEmail(
            $updated,
            $row['account_id'],
            $formData,
            $_SESSION['admin_email'] ?? null
        );
    } catch (Throwable $e) {
        if (function_exists('email_log')) {
            email_log('Admin notify email failed: ' . $e->getMessage(), 'error');
        }
    }

    header('Location: dashboard.php?msg=' . urlencode('Record resubmitted to CSE and updated.'));
    exit;
}

function renderField($field, $value, $formData = []) {
    $key = $field['key'];
    $name = 'f_' . $key;
    $val = is_scalar($value) ? (string)$value : json_encode($value);
    $escVal = htmlspecialchars($val);
    $escKey = htmlspecialchars($key);
    $label = htmlspecialchars($field['label']);
    $id = 'f_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
    $locked = !empty($field['locked']);
    $lockAttr = $locked ? ' readonly disabled' : '';
    $lockClass = $locked ? ' is-locked' : '';
    $lockIcon = $locked ? ' <i class="fas fa-lock" title="Locked - cannot be edited from admin"></i>' : '';

    $inner = '<div class="form-group' . $lockClass . '"><label for="' . $id . '">' . $label . $lockIcon . '</label>';

    switch ($field['type'] ?? 'text') {
        case 'yn':
            $optVal = $field['options'] ?? ['Y' => 'Yes', 'N' => 'No'];
            $inner .= '<div class="radio-group">';
            foreach ($optVal as $ov => $ol) {
                $checked = ($val === (string)$ov) ? ' checked' : '';
                $oid = $id . '_' . $ov;
                $inner .= '<div class="radio-option"><input type="radio" id="' . $oid . '" name="' . $name . '" value="' . htmlspecialchars($ov) . '"' . $checked . $lockAttr . '><label for="' . $oid . '">' . htmlspecialchars($ol) . '</label></div>';
            }
            $inner .= '</div>';
            break;
        case 'textarea':
            $inner .= '<textarea id="' . $id . '" name="' . $name . '" rows="3"' . $lockAttr . '>' . $escVal . '</textarea>';
            break;
        case 'email':
            $inner .= '<input type="email" id="' . $id . '" name="' . $name . '" value="' . $escVal . '"' . $lockAttr . '>';
            break;
        case 'tel':
            $inner .= '<input type="tel" id="' . $id . '" name="' . $name . '" value="' . $escVal . '"' . $lockAttr . '>';
            break;
        case 'date':
            $inner .= '<input type="date" id="' . $id . '" name="' . $name . '" value="' . $escVal . '"' . $lockAttr . '>';
            break;
        case 'select':
            $optList = $field['options'] ?? [];
            if ($val === 'undefined') $val = '';
            if ($val !== '' && !isset($optList[$val])) {
                $optList = [$val => $val] + $optList;
            }
            $inner .= '<select id="' . $id . '" name="' . $name . '"' . $lockAttr . '>';
            $inner .= '<option value="">Select</option>';
            foreach ($optList as $ov => $ol) {
                $selected = ($val === (string)$ov) ? ' selected' : '';
                $inner .= '<option value="' . htmlspecialchars($ov) . '"' . $selected . '>' . htmlspecialchars($ol) . '</option>';
            }
            $inner .= '</select>';
            break;
        default:
            $inner .= '<input type="text" id="' . $id . '" name="' . $name . '" value="' . $escVal . '"' . $lockAttr . '>';
    }
    $inner .= '</div>';

    // Optional wrapper for conditional visibility (single-field)
    $wrapperId = $field['wrapperId'] ?? null;
    if ($wrapperId) {
        $showWhen = $field['showWhen'] ?? [];
        $visible = true;
        foreach ($showWhen as $refKey => $refVal) {
            $actual = $formData[$refKey] ?? '';
            if (is_array($refVal)) {
                $visible = in_array($actual, $refVal);
            } else {
                $visible = ($actual === (string)$refVal);
            }
            if (!$visible) break;
        }
        $style = $visible ? '' : ' style="display:none"';
        return '<div id="' . htmlspecialchars($wrapperId) . '" class="admin-conditional-wrap"' . $style . '>' . $inner . '</div>';
    }
    return $inner;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $effectiveLocked ? 'View' : 'Edit' ?> - <?= htmlspecialchars($displayCseId) ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-status-banner { padding: 14px 20px; border-radius: 10px; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 12px; }
        .admin-status-banner i { font-size: 18px; margin-top: 2px; }
        .admin-status-banner.is-locked { background: #f3f4f6; border-left: 4px solid #6b7280; color: #374151; }
        .admin-status-banner.is-pending { background: #fff8e6; border-left: 4px solid #f59e0b; color: #92400e; }
        .admin-status-banner strong { display: block; margin-bottom: 2px; }
        .admin-status-banner p { margin: 0; font-size: 13px; line-height: 1.4; }
    </style>
</head>
<body class="admin-edit-page<?= $effectiveLocked ? ' is-readonly' : '' ?>">
    <header class="admin-edit-header">
        <h1>
            <i class="fas <?= $effectiveLocked ? 'fa-eye' : 'fa-edit' ?>"></i>
            <?= $effectiveLocked ? 'View' : 'Edit' ?>
            Account <?= htmlspecialchars($displayCseId) ?>
            <?php if ($isSuperadmin): ?><span style="font-size:11px; background:#DD4200; color:#fff; padding:3px 8px; border-radius:999px; vertical-align:middle; margin-left:8px; letter-spacing:0.5px;">SUPERADMIN</span><?php endif; ?>
        </h1>
        <div class="header-actions">
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </header>

    <main class="admin-edit-content">
        <p class="admin-meta">
            Created: <?= htmlspecialchars($row['created_at']) ?>
            &bull; Last updated: <?= htmlspecialchars($row['updated_at'] ?? $row['created_at']) ?>
            <?php if (!empty($row['submitted_to_cse_at'])): ?>
                &bull; Submitted to CSE: <?= htmlspecialchars($row['submitted_to_cse_at']) ?>
            <?php endif; ?>
        </p>

        <?php if ($forceViewMode && !$isLockedSubmission): ?>
        <div class="admin-status-banner is-pending">
            <i class="fas fa-eye"></i>
            <div>
                <strong>View mode</strong>
                <p>You opened this submission read-only. <a href="edit.php?id=<?= (int)$id ?>">Switch to edit mode</a> to make changes.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isLockedSubmission && $isSuperadmin): ?>
        <div class="admin-status-banner is-pending">
            <i class="fas fa-triangle-exclamation"></i>
            <div>
                <strong>This record was already submitted to CSE</strong>
                <p>You are editing it as a superadmin. Saving will resubmit the record to CSE via the API and notify the client.</p>
            </div>
        </div>
        <?php elseif ($isLockedSubmission): ?>
        <div class="admin-status-banner is-locked">
            <i class="fas fa-lock"></i>
            <div>
                <strong>This record has been submitted to CSE</strong>
                <p>It is now locked and read-only. Use the dashboard to delete it if necessary.</p>
            </div>
        </div>
        <?php elseif ($isPendingSubmission): ?>
        <div class="admin-status-banner is-pending">
            <i class="fas fa-hourglass-half"></i>
            <div>
                <strong>Pending review — not yet sent to CSE</strong>
                <p>Saving here updates the stored draft only. To send this to CSE, return to the dashboard and click <em>Approve &amp; Send to CSE</em>.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($_GET['error'])): ?>
        <div class="admin-edit-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
        </div>
        <?php endif; ?>

        <?php
        // Defense in depth: when the row is locked, force every field to render locked
        // and suppress the file-upload + submit controls below. Superadmins bypass
        // this lockdown (see $effectiveLocked above).
        if ($effectiveLocked) {
            foreach ($fieldConfig as $sectionTitle => &$_fields) {
                foreach ($_fields as &$_f) { $_f['locked'] = true; }
                unset($_f);
            }
            unset($_fields);
        }
        ?>

        <form method="post" class="admin-edit-form" enctype="multipart/form-data">
            <?php foreach ($fieldConfig as $sectionTitle => $fields):
                if (empty($fields)) continue;
                $icon = $sectionIcons[$sectionTitle] ?? 'fa-folder';
            ?>
            <div class="admin-section">
                <h2 class="admin-section-title"><i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($sectionTitle) ?></h2>
                <?php
                $inRow = false;
                $rowCount = 0;
                $prevGroup = null;
                foreach ($fields as $field):
                    $group = $field['inGroup'] ?? null;
                    // Close previous group when switching
                    if ($prevGroup && $group !== $prevGroup) {
                        echo '</div>';
                        $prevGroup = null;
                    }
                    // Open new group
                    if ($group && $group !== $prevGroup) {
                        $gShowWhen = $field['groupShowWhen'] ?? [];
                        $gVisible = true;
                        foreach ($gShowWhen as $refKey => $refVal) {
                            $actual = $formData[$refKey] ?? '';
                            if (is_array($refVal)) {
                                $gVisible = in_array($actual, $refVal);
                            } else {
                                $gVisible = ($actual === (string)$refVal);
                            }
                            if (!$gVisible) break;
                        }
                        echo '<div id="' . htmlspecialchars($group) . '" class="conditional-field admin-conditional-wrap"' . ($gVisible ? ' style="display:block"' : ' style="display:none"') . '>';
                        $prevGroup = $group;
                    }
                    $val = $formData[$field['key']] ?? '';
                    $isTextarea = ($field['type'] ?? '') === 'textarea';
                    if ($isTextarea && $inRow) { echo '</div>'; $inRow = false; $rowCount = 0; }
                    if (!$inRow && !$isTextarea) { echo '<div class="form-row">'; $inRow = true; }
                    echo renderField($field, $val, $formData);
                    if (!$isTextarea) {
                        $rowCount++;
                        if ($rowCount >= 2) { echo '</div>'; $inRow = false; $rowCount = 0; }
                    }
                endforeach;
                if ($prevGroup) echo '</div>';
                if ($inRow) echo '</div>';
                ?>
            </div>
            <?php endforeach; ?>

            <div class="admin-section">
                <h2 class="admin-section-title"><i class="fas fa-images"></i> Documents</h2>
                <?php if (!$effectiveLocked): ?>
                <p class="admin-upload-hint">Upload or replace documents (Max 2MB each, JPG/PNG only). New files override existing.</p>
                <?php endif; ?>
                <div class="admin-images-upload-grid">
                    <?php
                    $docTypes = [
                        'selfie' => ['label' => 'Selfie Photo', 'input' => 'selfie_upload'],
                        'nic_front' => ['label' => 'NIC Front', 'input' => 'nic_front_upload'],
                        'nic_back' => ['label' => 'NIC Back', 'input' => 'nic_back_upload'],
                        'passport' => ['label' => 'Passport (optional)', 'input' => 'passport_upload'],
                    ];
                    foreach ($docTypes as $key => $info):
                        $path = $imagePaths[$key] ?? null;
                    ?>
                    <div class="admin-image-upload-card">
                        <div class="admin-image-preview">
                            <?php if ($path): ?>
                            <a href="view_image.php?path=<?= urlencode($path) ?>" target="_blank" rel="noopener" title="Open <?= htmlspecialchars($info['label']) ?> in new tab">
                                <img src="view_image.php?path=<?= urlencode($path) ?>" alt="<?= htmlspecialchars($info['label']) ?>" loading="lazy" onerror="this.parentElement.parentElement.innerHTML='<span class=\'no-preview\'>No preview</span>'">
                            </a>
                            <?php else: ?>
                            <span class="no-preview">No image</span>
                            <?php endif; ?>
                        </div>
                        <div class="img-label"><?= htmlspecialchars($info['label']) ?></div>
                        <?php if (!$effectiveLocked): ?>
                        <input type="file" id="<?= htmlspecialchars($info['input']) ?>" name="<?= htmlspecialchars($info['input']) ?>" accept="image/jpeg,image/png">
                        <div class="admin-upload-error" style="display:none;color:#c0392b;font-size:12px;margin-top:4px;"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-section">
                <h2 class="admin-section-title"><i class="fas fa-folder-open"></i> Supporting Documents</h2>
                <p class="admin-upload-hint">
                    Optional client-supplied documents (utility bills, bank statements, etc.).
                    These are stored on the server only and are <strong>never sent to CSE</strong>.
                    PDF/JPG/PNG, max 2MB per file.
                </p>

                <div id="supporting-docs-grid" class="supporting-docs-grid">
                    <?php
                    $sdTypes = get_supporting_doc_types();
                    $existingByKey = [];
                    foreach ($supportingDocs['categories'] as $cat) {
                        $existingByKey[$cat['id']] = $cat;
                    }

                    foreach ($sdTypes as $sdKey => $sdLabel):
                        $cat = $existingByKey[$sdKey] ?? null;
                    ?>
                    <div class="supporting-doc-card" data-supporting-key="<?= htmlspecialchars($sdKey) ?>" data-supporting-custom="0">
                        <div class="supporting-doc-card-head">
                            <div class="supporting-doc-title"><?= htmlspecialchars($sdLabel) ?></div>
                        </div>
                        <ul class="supporting-doc-files" data-supporting-files-for="<?= htmlspecialchars($sdKey) ?>">
                            <?php if ($cat): foreach ($cat['files'] as $f):
                                $isPdf = ($f['mime'] === 'application/pdf') || preg_match('/\.pdf$/i', $f['name']);
                                $isImg = !$isPdf && (preg_match('/^image\//i', $f['mime'] ?? '') || preg_match('/\.(jpe?g|png|gif|webp)$/i', $f['name']));
                                $viewUrl = 'view_document.php?path=' . urlencode($f['path']);
                            ?>
                            <li class="supporting-doc-file supporting-doc-file-existing">
                                <span class="supporting-doc-thumb<?= $isImg ? '' : ' is-icon' ?>">
                                    <?php if ($isImg): ?>
                                    <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" rel="noopener">
                                        <img src="<?= htmlspecialchars($viewUrl) ?>" alt="<?= htmlspecialchars($f['name']) ?>" loading="lazy">
                                    </a>
                                    <?php else: ?>
                                    <i class="fas fa-file-pdf"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="supporting-doc-meta">
                                    <a class="file-name file-name-link" href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($f['name']) ?></a>
                                    <span class="file-size"><?= htmlspecialchars(supporting_docs_human_size((int)$f['size'])) ?></span>
                                </span>
                                <?php if (!$effectiveLocked): ?>
                                <label class="file-remove-toggle" title="Mark for removal" style="display:flex;align-items:center;gap:4px;font-size:12px;color:#c0392b;cursor:pointer;">
                                    <input type="checkbox" name="supporting_remove[]" value="<?= htmlspecialchars($f['id']) ?>"> Remove
                                </label>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; endif; ?>
                        </ul>
                        <?php if (!$effectiveLocked): ?>
                        <label class="supporting-doc-picker">
                            <input type="file" multiple accept="application/pdf,image/jpeg,image/png,image/gif,image/webp"
                                class="supporting-doc-input" data-supporting-key="<?= htmlspecialchars($sdKey) ?>">
                            <span class="supporting-doc-picker-btn"><i class="fas fa-plus"></i> Add files</span>
                        </label>
                        <div class="supporting-doc-error" style="display:none;"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <?php
                    foreach ($supportingDocs['categories'] as $cat):
                        if (!$cat['custom']) continue;
                    ?>
                    <div class="supporting-doc-card supporting-doc-card-custom" data-supporting-custom="1" data-supporting-existing-id="<?= htmlspecialchars($cat['id']) ?>">
                        <div class="supporting-doc-card-head">
                            <?php if (!$effectiveLocked): ?>
                            <input type="text" class="supporting-doc-custom-label" data-existing-label="1" value="<?= htmlspecialchars($cat['label']) ?>" maxlength="80">
                            <?php else: ?>
                            <div class="supporting-doc-title"><?= htmlspecialchars($cat['label']) ?></div>
                            <?php endif; ?>
                        </div>
                        <ul class="supporting-doc-files">
                            <?php foreach ($cat['files'] as $f):
                                $isPdf = ($f['mime'] === 'application/pdf') || preg_match('/\.pdf$/i', $f['name']);
                                $isImg = !$isPdf && (preg_match('/^image\//i', $f['mime'] ?? '') || preg_match('/\.(jpe?g|png|gif|webp)$/i', $f['name']));
                                $viewUrl = 'view_document.php?path=' . urlencode($f['path']);
                            ?>
                            <li class="supporting-doc-file supporting-doc-file-existing">
                                <span class="supporting-doc-thumb<?= $isImg ? '' : ' is-icon' ?>">
                                    <?php if ($isImg): ?>
                                    <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" rel="noopener">
                                        <img src="<?= htmlspecialchars($viewUrl) ?>" alt="<?= htmlspecialchars($f['name']) ?>" loading="lazy">
                                    </a>
                                    <?php else: ?>
                                    <i class="fas fa-file-pdf"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="supporting-doc-meta">
                                    <a class="file-name file-name-link" href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($f['name']) ?></a>
                                    <span class="file-size"><?= htmlspecialchars(supporting_docs_human_size((int)$f['size'])) ?></span>
                                </span>
                                <?php if (!$effectiveLocked): ?>
                                <label class="file-remove-toggle" title="Mark for removal" style="display:flex;align-items:center;gap:4px;font-size:12px;color:#c0392b;cursor:pointer;">
                                    <input type="checkbox" name="supporting_remove[]" value="<?= htmlspecialchars($f['id']) ?>"> Remove
                                </label>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!$effectiveLocked): ?>
                        <label class="supporting-doc-picker">
                            <input type="file" multiple accept="application/pdf,image/jpeg,image/png,image/gif,image/webp"
                                class="supporting-doc-input">
                            <span class="supporting-doc-picker-btn"><i class="fas fa-plus"></i> Add files</span>
                        </label>
                        <div class="supporting-doc-error" style="display:none;"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!$effectiveLocked): ?>
                <div class="supporting-docs-actions">
                    <button type="button" id="add-custom-supporting-doc" class="btn btn-secondary">
                        <i class="fas fa-plus-circle"></i> Add Custom Document Type
                    </button>
                </div>

                <template id="supporting-doc-custom-template">
                    <div class="supporting-doc-card supporting-doc-card-custom" data-supporting-custom="1">
                        <div class="supporting-doc-card-head">
                            <input type="text" class="supporting-doc-custom-label" placeholder="Document name" maxlength="80">
                            <button type="button" class="supporting-doc-remove-card" title="Remove this category">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <label class="supporting-doc-picker">
                            <input type="file" multiple accept="application/pdf,image/jpeg,image/png,image/gif,image/webp"
                                class="supporting-doc-input">
                            <span class="supporting-doc-picker-btn"><i class="fas fa-plus"></i> Add files</span>
                        </label>
                        <ul class="supporting-doc-files"></ul>
                        <div class="supporting-doc-error" style="display:none;"></div>
                    </div>
                </template>
                <?php endif; ?>
            </div>

            <?php if (!$effectiveLocked): ?>
            <div class="form-actions">
                <?php if ($isPendingSubmission): ?>
                <button type="submit" class="btn-save" id="admin-submit-btn"><i class="fas fa-save"></i> Save Draft</button>
                <?php else: ?>
                <button type="submit" class="btn-save" id="admin-submit-btn"><i class="fas fa-paper-plane"></i> Save &amp; Resubmit to CSE</button>
                <?php endif; ?>
                <a href="dashboard.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
            </div>
            <?php else: ?>
            <div class="form-actions">
                <a href="dashboard.php" class="btn-cancel"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
            <?php endif; ?>
        </form>
    </main>
    <script>
    (function() {
        var form = document.querySelector('.admin-edit-form');
        var submitBtn = document.getElementById('admin-submit-btn');
        var MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB
        var ALLOWED_TYPES = ['image/jpeg', 'image/png'];
        function validateImageFile(file) {
            if (file.size > MAX_FILE_SIZE) return 'File size must be less than 2MB';
            if (!ALLOWED_TYPES.includes(file.type)) return 'Only JPG and PNG files are allowed';
            return null;
        }
        function showUploadError(card, msg) {
            var err = card.querySelector('.admin-upload-error');
            if (err) { err.textContent = msg; err.style.display = 'block'; }
        }
        function clearUploadError(card) {
            var err = card.querySelector('.admin-upload-error');
            if (err) { err.textContent = ''; err.style.display = 'none'; }
        }
        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                var fileInputs = form.querySelectorAll('.admin-image-upload-card input[type="file"]');
                for (var i = 0; i < fileInputs.length; i++) {
                    var input = fileInputs[i];
                    if (input.files && input.files[0]) {
                        var err = validateImageFile(input.files[0]);
                        if (err) {
                            e.preventDefault();
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Save & Resubmit to CSE';
                            submitBtn.classList.remove('btn-loading');
                            showUploadError(input.closest('.admin-image-upload-card'), err);
                            return false;
                        }
                    }
                }
                if (submitBtn.disabled) return false;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                submitBtn.classList.add('btn-loading');
            });
        }
        document.querySelectorAll('.admin-image-upload-card input[type="file"]').forEach(function(input) {
            input.addEventListener('change', function() {
                var card = this.closest('.admin-image-upload-card');
                var preview = card.querySelector('.admin-image-preview');
                if (!this.files || !this.files[0]) { clearUploadError(card); return; }
                var err = validateImageFile(this.files[0]);
                if (err) {
                    showUploadError(card, err);
                    this.value = '';
                    return;
                }
                clearUploadError(card);
                var fr = new FileReader();
                fr.onload = function() {
                    preview.innerHTML = '<a href="' + fr.result + '" target="_blank" rel="noopener" title="Open full size">'
                        + '<img src="' + fr.result + '" alt="Preview">'
                        + '</a>';
                };
                fr.readAsDataURL(this.files[0]);
            });
        });

        // ==================== SUPPORTING DOCUMENTS (admin) ====================
        // We capture pending picks per card in JS, then on submit build a multipart
        // FormData with `supporting_meta` + `supporting_<field>[]` arrays, mirroring
        // exactly what api.php expects. Existing files are managed via the inline
        // "Remove" checkboxes which post `supporting_remove[]` natively.
        var SD_MAX_SIZE = 2 * 1024 * 1024;
        var SD_ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
        var sdCustomCounter = 0;

        function sdValidate(file) {
            if (!file || file.size <= 0 || file.size > SD_MAX_SIZE) return 'exceeds 2MB';
            var dot = file.name.lastIndexOf('.');
            var ext = dot === -1 ? '' : file.name.slice(dot + 1).toLowerCase();
            if (SD_ALLOWED_EXT.indexOf(ext) === -1) return 'unsupported file type';
            return null;
        }

        function sdShowError(card, msg) {
            var err = card.querySelector('.supporting-doc-error');
            if (err) { err.textContent = msg; err.style.display = msg ? 'block' : 'none'; }
        }

        function sdHumanSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(2) + ' MB';
        }

        function sdIsImage(name, mime) {
            if (mime && /^image\//i.test(mime)) return true;
            return /\.(jpe?g|png|gif|webp)$/i.test(name || '');
        }

        function sdRenderPending(card) {
            var ul = card.querySelector('.supporting-doc-files');
            if (!ul) return;
            // Drop existing pending nodes (and revoke their blob URLs); keep server-side ones.
            ul.querySelectorAll('.supporting-doc-file-pending').forEach(function(n) {
                n.querySelectorAll('img[src^="blob:"], a[href^="blob:"]').forEach(function(el) {
                    var u = el.src || el.href;
                    if (u) { try { URL.revokeObjectURL(u); } catch (e) {} }
                });
                n.remove();
            });
            var pending = card.__sdPending || [];
            pending.forEach(function(file, idx) {
                var li = document.createElement('li');
                li.className = 'supporting-doc-file supporting-doc-file-pending';
                var isImg = sdIsImage(file.name, file.type);

                var thumb = document.createElement('span');
                thumb.className = 'supporting-doc-thumb' + (isImg ? '' : ' is-icon');
                var blobUrl = '';
                try { blobUrl = URL.createObjectURL(file); } catch (e) {}
                if (isImg && blobUrl) {
                    var img = document.createElement('img');
                    img.alt = file.name;
                    img.src = blobUrl;
                    thumb.appendChild(img);
                } else {
                    var ic = document.createElement('i');
                    ic.className = 'fas fa-file-pdf';
                    thumb.appendChild(ic);
                }

                var meta = document.createElement('span');
                meta.className = 'supporting-doc-meta';
                var nameEl;
                if (blobUrl) {
                    nameEl = document.createElement('a');
                    nameEl.href = blobUrl;
                    nameEl.target = '_blank';
                    nameEl.rel = 'noopener';
                    nameEl.className = 'file-name file-name-link';
                } else {
                    nameEl = document.createElement('span');
                    nameEl.className = 'file-name';
                }
                nameEl.textContent = file.name;
                var sizeEl = document.createElement('span');
                sizeEl.className = 'file-size';
                sizeEl.textContent = sdHumanSize(file.size);
                meta.appendChild(nameEl);
                meta.appendChild(sizeEl);

                var rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'file-remove';
                rm.title = 'Remove';
                rm.innerHTML = '<i class="fas fa-times"></i>';
                rm.addEventListener('click', function() {
                    if (blobUrl) { try { URL.revokeObjectURL(blobUrl); } catch (e) {} }
                    pending.splice(idx, 1);
                    sdRenderPending(card);
                });

                li.appendChild(thumb);
                li.appendChild(meta);
                li.appendChild(rm);
                ul.appendChild(li);
            });
        }

        function sdAttachFileInput(card) {
            var input = card.querySelector('.supporting-doc-input');
            if (!input || input.__sdBound) return;
            input.__sdBound = true;
            card.__sdPending = card.__sdPending || [];
            input.addEventListener('change', function() {
                sdShowError(card, '');
                var errors = [];
                for (var i = 0; i < this.files.length; i++) {
                    var f = this.files[i];
                    var msg = sdValidate(f);
                    if (msg) { errors.push(f.name + ': ' + msg); continue; }
                    if (card.__sdPending.some(function(p) { return p.name === f.name && p.size === f.size; })) continue;
                    card.__sdPending.push(f);
                }
                this.value = '';
                sdRenderPending(card);
                if (errors.length) sdShowError(card, errors.join('. '));
            });
        }

        document.querySelectorAll('#supporting-docs-grid .supporting-doc-card').forEach(sdAttachFileInput);

        var sdAddBtn = document.getElementById('add-custom-supporting-doc');
        if (sdAddBtn) {
            sdAddBtn.addEventListener('click', function() {
                var tpl = document.getElementById('supporting-doc-custom-template');
                var grid = document.getElementById('supporting-docs-grid');
                if (!tpl || !grid) return;
                var node = tpl.content.firstElementChild.cloneNode(true);
                grid.appendChild(node);
                sdAttachFileInput(node);
                var removeBtn = node.querySelector('.supporting-doc-remove-card');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function() { node.remove(); });
                }
                var labelInput = node.querySelector('.supporting-doc-custom-label');
                if (labelInput) labelInput.focus();
            });
        }

        // On submit: hijack the natural submit, build a FormData, post via fetch
        // so we can attach pending files keyed by the manifest. Existing remove
        // checkboxes are still picked up because we copy all native form fields.
        function sdBuildAndSubmit(originalEvent) {
            if (!form || form.__sdSubmitting) return;
            // Skip when the form has no supporting-docs section (defensive).
            if (!document.getElementById('supporting-docs-grid')) return;

            originalEvent.preventDefault();
            form.__sdSubmitting = true;

            // Validate custom labels for any card that has either pending files
            // or existing files.
            var labelOk = true;
            document.querySelectorAll('#supporting-docs-grid .supporting-doc-card-custom').forEach(function(card) {
                var labelInput = card.querySelector('.supporting-doc-custom-label');
                var hasPending = (card.__sdPending || []).length > 0;
                var hasExisting = card.querySelectorAll('.supporting-doc-file-existing').length > 0;
                if ((hasPending || hasExisting) && labelInput && !labelInput.value.trim()) {
                    sdShowError(card, 'Please enter a name for this document type.');
                    if (labelOk) labelInput.focus();
                    labelOk = false;
                }
            });
            if (!labelOk) {
                form.__sdSubmitting = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-loading');
                }
                return;
            }

            var fd = new FormData(form);

            // Build supporting_meta + append pending files.
            var meta = [];
            document.querySelectorAll('#supporting-docs-grid .supporting-doc-card').forEach(function(card, idx) {
                var pending = card.__sdPending || [];
                if (!pending.length) return;
                var isCustom = card.dataset.supportingCustom === '1';
                var fieldName, entry;
                if (isCustom) {
                    var labelInput = card.querySelector('.supporting-doc-custom-label');
                    var label = (labelInput && labelInput.value || '').trim();
                    if (!label) return;
                    fieldName = 'supporting_custom_' + idx + '_' + Date.now();
                    entry = { id: card.dataset.supportingExistingId || '', label: label, custom: true, field: fieldName };
                } else {
                    var key = card.dataset.supportingKey;
                    if (!key) return;
                    fieldName = 'supporting_' + key;
                    entry = { id: key, label: '', custom: false, field: fieldName };
                }
                meta.push(entry);
                pending.forEach(function(f) { fd.append(fieldName + '[]', f, f.name); });
            });
            // Also propagate any existing custom-card label edits even when no
            // new files are added — we send a meta entry without files so the
            // server can reach the merge branch (it just won't upload anything,
            // but a future enhancement could let the user rename).
            if (meta.length) fd.append('supporting_meta', JSON.stringify(meta));

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                submitBtn.classList.add('btn-loading');
            }

            fetch(form.action || window.location.href, {
                method: 'POST',
                body: fd,
                redirect: 'follow'
            }).then(function(resp) {
                if (resp.redirected) {
                    window.location.href = resp.url;
                } else {
                    return resp.text().then(function(text) {
                        document.open();
                        document.write(text);
                        document.close();
                    });
                }
            }).catch(function(err) {
                form.__sdSubmitting = false;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-loading');
                }
                alert('Submission failed: ' + err.message);
            });
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                if (form.__sdSubmitting) return;
                // The earlier-registered handler already validated ID-image picks
                // and either preventDefault'd (on error) or flipped submitBtn into
                // loading mode (on success). We only want to take over on success.
                if (e.defaultPrevented) return;
                sdBuildAndSubmit(e);
            });
        }

        // Conditional field toggles (same logic as main form)
        function getVal(idOrName, isRadio) {
            if (isRadio) {
                var el = document.querySelector('input[name="f_' + idOrName + '"]:checked');
                return el ? el.value : '';
            }
            var el = document.getElementById('f_' + idOrName);
            return el ? el.value : '';
        }
        function toggleIdentificationFields() {
            var idType = getVal('IdentificationProof', false);
            var els = { nic: document.getElementById('nic-field'), passport: document.getElementById('passport-field'), passportExp: document.getElementById('passport-exp-field') };
            var showNIC = (idType === 'NIC'), showPassport = (idType === 'Passport');
            if (els.nic) els.nic.style.display = showNIC ? 'block' : 'none';
            if (els.passport) els.passport.style.display = showPassport ? 'block' : 'none';
            if (els.passportExp) els.passportExp.style.display = showPassport ? 'block' : 'none';
        }
        function toggleCDSAccount() {
            var v = getVal('ExitCDSAccount', true);
            var el = document.getElementById('cds-account-field');
            if (el) el.style.display = (v === 'Y') ? 'block' : 'none';
        }
        function toggleEmploymentFields() {
            var v = getVal('EmployeStatus', false);
            var el = document.getElementById('employment-details');
            if (el) el.style.display = (v === 'Y' || v === 'S') ? 'block' : 'none';
        }
        function togglePEPQuestions() {
            var v = getVal('IsPEP', true);
            var el = document.getElementById('pep-questions');
            if (el) el.style.display = (v === 'Y') ? 'block' : 'none';
        }
        function toggleLitigationDetails() {
            var v = getVal('LitigationStatus', true);
            var el = document.getElementById('litigation-details');
            if (el) el.style.display = (v === 'Y') ? 'block' : 'none';
        }
        function runAllToggles() {
            toggleIdentificationFields();
            toggleCDSAccount();
            toggleEmploymentFields();
            togglePEPQuestions();
            toggleLitigationDetails();
        }
        runAllToggles();
        document.getElementById('f_IdentificationProof') && document.getElementById('f_IdentificationProof').addEventListener('change', toggleIdentificationFields);
        document.getElementById('f_EmployeStatus') && document.getElementById('f_EmployeStatus').addEventListener('change', toggleEmploymentFields);
        document.querySelectorAll('input[name="f_ExitCDSAccount"]').forEach(function(r) { r.addEventListener('change', toggleCDSAccount); });
        document.querySelectorAll('input[name="f_IsPEP"]').forEach(function(r) { r.addEventListener('change', togglePEPQuestions); });
        document.querySelectorAll('input[name="f_LitigationStatus"]').forEach(function(r) { r.addEventListener('change', toggleLitigationDetails); });
    })();
    </script>
</body>
</html>
