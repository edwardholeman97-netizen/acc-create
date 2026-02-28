<?php
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/database/connection.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getDb();
$stmt = $pdo->prepare('SELECT id, account_id, form_data, image_paths, created_at, updated_at FROM cds_submissions WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: dashboard.php?msg=' . urlencode('Record not found.'));
    exit;
}

$formData = json_decode($row['form_data'], true) ?: [];
$imagePaths = json_decode($row['image_paths'] ?? '{}', true) ?: [];

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
        ['key' => 'MobileNo', 'label' => 'Mobile Number', 'type' => 'tel'],
        ['key' => 'TelphoneNo', 'label' => 'Telephone Number', 'type' => 'tel'],
        ['key' => 'Email', 'label' => 'Email Address', 'type' => 'email'],
        ['key' => 'DateOfBirthday', 'label' => 'Date of Birth', 'type' => 'date'],
        ['key' => 'Gender', 'label' => 'Gender', 'type' => 'yn', 'options' => ['M' => 'Male', 'F' => 'Female']],
    ],
    'Identification' => [
        ['key' => 'IdentificationProof', 'label' => 'Identification Proof', 'type' => 'select', 'options' => get_form_id_proof_options()],
        ['key' => 'NicNo', 'label' => 'NIC No', 'type' => 'text', 'wrapperId' => 'nic-field', 'showWhen' => ['IdentificationProof' => 'NIC']],
        ['key' => 'PassportNo', 'label' => 'Passport No', 'type' => 'text', 'wrapperId' => 'passport-field', 'showWhen' => ['IdentificationProof' => 'Passport']],
        ['key' => 'PassportExpDate', 'label' => 'Passport Expiry Date', 'type' => 'date', 'wrapperId' => 'passport-exp-field', 'showWhen' => ['IdentificationProof' => 'Passport']],
    ],
    'Investment' => [
        ['key' => 'BrokerFirm', 'label' => 'Stock Broker Firm', 'type' => 'select', 'options' => $brokersOptions],
        ['key' => 'ExitCDSAccount', 'label' => 'Existing CDS Account', 'type' => 'yn'],
        ['key' => 'CDSAccountNo', 'label' => 'CDS Account Number', 'type' => 'text', 'wrapperId' => 'cds-account-field', 'showWhen' => ['ExitCDSAccount' => 'Y']],
        ['key' => 'TinNo', 'label' => 'TIN Number', 'type' => 'text'],
        ['key' => 'InvestorId', 'label' => 'Investor / Advisor', 'type' => 'text'],
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
        ['key' => 'BankAccountNo', 'label' => 'Bank Account Number', 'type' => 'text'],
        ['key' => 'BankCode', 'label' => 'Bank Code', 'type' => 'text'],
        ['key' => 'BankBranch', 'label' => 'Bank Branch', 'type' => 'text'],
        ['key' => 'BankAccountType', 'label' => 'Bank Account Type', 'type' => 'text'],
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

$usedKeys = [];
foreach ($fieldConfig as $section => $fields) {
    foreach ($fields as $f) {
        $usedKeys[$f['key']] = true;
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
    $updated = [];
    foreach ($formData as $k => $v) {
        $updated[$k] = $_POST['f_' . $k] ?? $v;
    }
    foreach ($_POST as $pk => $pv) {
        if (strpos($pk, 'f_') === 0) {
            $key = substr($pk, 2);
            if (!isset($formData[$key])) {
                $updated[$key] = $pv;
            }
        }
    }

    // Ensure system fields for CSE API
    $updated['ClientType'] = $updated['ClientType'] ?? 'FI';
    $updated['Residency'] = $updated['Residency'] ?? 'R';
    $updated['ApiUser'] = $updated['ApiUser'] ?? 'DIALOG';

    // Save any newly uploaded images (merge with existing)
    $newPaths = [];
    $fileMap = ['selfie_upload' => 'selfie', 'nic_front_upload' => 'nic_front', 'nic_back_upload' => 'nic_back', 'passport_upload' => 'passport'];
    $baseDir = rtrim(CSE_STORAGE_PATH, '/') . '/uploads/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $row['account_id']);
    foreach ($fileMap as $fileKey => $baseName) {
        if (!empty($_FILES[$fileKey]['tmp_name']) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($baseDir)) @mkdir($baseDir, 0750, true);
            $ext = pathinfo($_FILES[$fileKey]['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg';
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png'], true)) $ext = 'jpg';
            $dest = $baseDir . '/' . $baseName . '.' . $ext;
            if (@copy($_FILES[$fileKey]['tmp_name'], $dest)) {
                $newPaths[$baseName] = 'uploads/' . basename($baseDir) . '/' . basename($dest);
            }
        }
    }
    if (!empty($newPaths)) {
        $imagePaths = array_merge($imagePaths, $newPaths);
    }

    // Resubmit to CSE API (form data + images with doc payload); only save to DB if successful
    require_once dirname(__DIR__) . '/lib/cse_api.php';
    $resubmitResult = cse_resubmitToApi($updated, $row['account_id'], $imagePaths);

    if (!$resubmitResult['success']) {
        header('Location: edit.php?id=' . $id . '&error=' . urlencode($resubmitResult['message']));
        exit;
    }

    $stmt = $pdo->prepare('UPDATE cds_submissions SET form_data = ?, image_paths = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([json_encode($updated, JSON_UNESCAPED_UNICODE), json_encode($imagePaths, JSON_UNESCAPED_UNICODE), $id]);
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

    $inner = '<div class="form-group"><label for="' . $id . '">' . $label . '</label>';

    switch ($field['type'] ?? 'text') {
        case 'yn':
            $optVal = $field['options'] ?? ['Y' => 'Yes', 'N' => 'No'];
            $inner .= '<div class="radio-group">';
            foreach ($optVal as $ov => $ol) {
                $checked = ($val === (string)$ov) ? ' checked' : '';
                $oid = $id . '_' . $ov;
                $inner .= '<div class="radio-option"><input type="radio" id="' . $oid . '" name="' . $name . '" value="' . htmlspecialchars($ov) . '"' . $checked . '><label for="' . $oid . '">' . htmlspecialchars($ol) . '</label></div>';
            }
            $inner .= '</div>';
            break;
        case 'textarea':
            $inner .= '<textarea id="' . $id . '" name="' . $name . '" rows="3">' . $escVal . '</textarea>';
            break;
        case 'email':
            $inner .= '<input type="email" id="' . $id . '" name="' . $name . '" value="' . $escVal . '">';
            break;
        case 'tel':
            $inner .= '<input type="tel" id="' . $id . '" name="' . $name . '" value="' . $escVal . '">';
            break;
        case 'date':
            $inner .= '<input type="date" id="' . $id . '" name="' . $name . '" value="' . $escVal . '">';
            break;
        case 'select':
            $optList = $field['options'] ?? [];
            // Ignore "undefined" (bug from old main form using wrong API key)
            if ($val === 'undefined') $val = '';
            // Include stored value if not in options (e.g. from older submission)
            if ($val !== '' && !isset($optList[$val])) {
                $optList = [$val => $val] + $optList;
            }
            $inner .= '<select id="' . $id . '" name="' . $name . '">';
            $inner .= '<option value="">Select</option>';
            foreach ($optList as $ov => $ol) {
                $selected = ($val === (string)$ov) ? ' selected' : '';
                $inner .= '<option value="' . htmlspecialchars($ov) . '"' . $selected . '>' . htmlspecialchars($ol) . '</option>';
            }
            $inner .= '</select>';
            break;
        default:
            $inner .= '<input type="text" id="' . $id . '" name="' . $name . '" value="' . $escVal . '">';
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
    <title>Edit - <?= htmlspecialchars($row['account_id']) ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-edit-page">
    <header class="admin-edit-header">
        <h1><i class="fas fa-edit"></i> Edit Account <?= htmlspecialchars($row['account_id']) ?></h1>
        <div class="header-actions">
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </header>

    <main class="admin-edit-content">
        <p class="admin-meta">Created: <?= htmlspecialchars($row['created_at']) ?> &bull; Last updated: <?= htmlspecialchars($row['updated_at'] ?? $row['created_at']) ?></p>

        <?php if (!empty($_GET['error'])): ?>
        <div class="admin-edit-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
        </div>
        <?php endif; ?>

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
                <p class="admin-upload-hint">Upload or replace documents (Max 2MB each, JPG/PNG only). New files override existing.</p>
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
                            <img src="view_image.php?path=<?= urlencode($path) ?>" alt="<?= htmlspecialchars($info['label']) ?>" loading="lazy" onerror="this.parentElement.innerHTML='<span class=\'no-preview\'>No preview</span>'">
                            <?php else: ?>
                            <span class="no-preview">No image</span>
                            <?php endif; ?>
                        </div>
                        <div class="img-label"><?= htmlspecialchars($info['label']) ?></div>
                        <input type="file" id="<?= htmlspecialchars($info['input']) ?>" name="<?= htmlspecialchars($info['input']) ?>" accept="image/jpeg,image/png">
                        <div class="admin-upload-error" style="display:none;color:#c0392b;font-size:12px;margin-top:4px;"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save" id="admin-submit-btn"><i class="fas fa-paper-plane"></i> Save & Resubmit to CSE</button>
                <a href="dashboard.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
            </div>
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
                    preview.innerHTML = '<img src="' + fr.result + '" alt="Preview">';
                };
                fr.readAsDataURL(this.files[0]);
            });
        });

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
