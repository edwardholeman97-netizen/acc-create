<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/includes/form_constants.php';
require_once __DIR__ . '/lib/cse_api.php';
require_once __DIR__ . '/lib/email.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (CORS_ALLOW_ORIGIN ?: '*'));
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$response = ['success' => false, 'message' => '', 'submissionUid' => null, 'accountId' => null];

try {
    liveLog('API Request Started');

    // ==================== PARSE REQUEST ====================
    $formData = [];
    $step = null;

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if ($contentType && strpos($contentType, 'multipart/form-data') !== false) {
        liveLog('Request is multipart/form-data');
        foreach ($_POST as $key => $value) {
            $formData[$key] = $value;
        }
        $step = $formData['step'] ?? null;
        // For client-resubmit, full form data is sent as a JSON string under "formData"
        if (isset($_POST['formData'])) {
            $decoded = json_decode($_POST['formData'], true);
            if (is_array($decoded)) {
                $formData = array_merge($formData, $decoded);
            }
        }
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

    if (empty($formData) && $step !== 'upload') {
        throw new Exception('No form data received');
    }
    liveLog('Form data processed. Step: ' . ($step ?: 'submit'));

    // ==================== STEP: UPLOAD IMAGES (after pending submission) ====================
    if ($step === 'upload') {
        $submissionUid = trim($_POST['submissionUid'] ?? $formData['submissionUid'] ?? '');
        if ($submissionUid === '') {
            throw new Exception('Submission UID required for image upload');
        }
        $submissionUid = preg_replace('/[^a-zA-Z0-9_-]/', '', $submissionUid);
        if ($submissionUid === '') {
            throw new Exception('Invalid submission UID');
        }

        liveLog('Step UPLOAD: Saving images for submission_uid=' . $submissionUid);

        $imagePaths = saveImagesToStorageByUid($submissionUid);
        updateSubmissionImagesByUid($submissionUid, $imagePaths);

        $response['success'] = true;
        $response['message'] = 'Images uploaded';
        $response['submissionUid'] = $submissionUid;
        $response['imagesUploaded'] = !empty($imagePaths);
        liveLog('Upload step completed for ' . $submissionUid);
        echo json_encode($response);
        exit;
    }

    // ==================== STEP: CLIENT RESUBMIT (token-gated, pushes directly to CSE) ====================
    if ($step === 'client-resubmit') {
        $rawToken = trim($_POST['token'] ?? $formData['token'] ?? '');
        if ($rawToken === '') {
            throw new Exception('Edit link token is required');
        }
        $row = consumeEditTokenForClient($rawToken);
        if (!$row) {
            throw new Exception('This edit link is invalid, expired, or has reached its usage limit.');
        }
        $submissionId = (int)$row['submission_id'];
        $submission = $row['submission'];

        // Restore stored values for permanently-locked fields. Defense in depth — the UI
        // already disables them, but we never trust client input for these.
        $storedForm = $submission['form_data'];
        foreach (get_form_locked_field_keys() as $lockedKey) {
            if (array_key_exists($lockedKey, $storedForm)) {
                $formData[$lockedKey] = $storedForm[$lockedKey];
            } else {
                unset($formData[$lockedKey]);
            }
        }

        $submissionUid = $submission['submission_uid'];

        // If new files were uploaded with this resubmit, persist them under the same uid folder.
        $newImagePaths = saveImagesToStorageByUid($submissionUid);
        $existingImagePaths = $submission['image_paths'] ?? [];
        $mergedImagePaths = array_merge($existingImagePaths, $newImagePaths);
        if (!empty($newImagePaths)) {
            updateSubmissionImagesByUid($submissionUid, $mergedImagePaths);
        }

        // Push to CSE as a new account (Status=1, AccountID=0).
        $pushResult = cse_pushPendingToApi($formData, $mergedImagePaths);
        if (!$pushResult['success']) {
            throw new Exception($pushResult['message'] ?: 'Failed to submit to CSE');
        }
        $cseAccountId = (string)$pushResult['accountId'];

        // Mark submission as accepted; clear admin note; record CSE id.
        markSubmissionSubmittedToCse($submissionId, $cseAccountId, $formData);

        // Send confirmation emails (best-effort).
        try {
            sendAccountCreationEmail($formData, $cseAccountId);
        } catch (Throwable $e) {
            liveLog('Account creation email failed: ' . $e->getMessage(), 'error');
        }

        $response['success'] = true;
        $response['message'] = 'Submitted to CSE';
        $response['submissionUid'] = $submissionUid;
        $response['accountId'] = $cseAccountId;
        liveLog('Client resubmit completed. CSE AccountID=' . $cseAccountId);
        echo json_encode($response);
        exit;
    }

    // ==================== STEP: SUBMIT (first-time client submission, DB-only) ====================
    // No CSE call here. The admin reviews the row and either approves it (push to CSE)
    // or sends an edit link back to the client.
    validateRequiredSaveUserFields($formData);

    $submissionUid = bin2hex(random_bytes(16));
    saveSubmissionPending($submissionUid, $formData);

    try {
        sendClientPendingReviewEmail($formData, $submissionUid);
    } catch (Throwable $e) {
        liveLog('Pending review email failed: ' . $e->getMessage(), 'error');
    }

    $response['success'] = true;
    $response['message'] = 'Submitted for review';
    $response['submissionUid'] = $submissionUid;
    liveLog('Submit step completed. submission_uid=' . $submissionUid);

} catch (Exception $e) {
    liveLog('Error: ' . $e->getMessage(), 'error');
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);


// ==================== DB HELPERS ====================

/**
 * Insert a brand-new pending review row. account_id is NULL until CSE accepts.
 */
function saveSubmissionPending(string $submissionUid, array $formData): void {
    $pdo = getDb();
    $stmt = $pdo->prepare(
        'INSERT INTO cds_submissions (submission_uid, account_id, cse_account_id, form_data, status)'
        . ' VALUES (?, NULL, NULL, ?, ?)'
    );
    $stmt->execute([
        $submissionUid,
        json_encode($formData, JSON_UNESCAPED_UNICODE),
        'pending_review',
    ]);
    liveLog('Saved pending submission: submission_uid=' . $submissionUid);
}

/**
 * Persist uploaded files to storage/uploads/<submission_uid>/.
 * Returns map of label => relative path (e.g. "uploads/abc123/selfie.png").
 */
function saveImagesToStorageByUid(string $submissionUid): array {
    $safeUid = preg_replace('/[^a-zA-Z0-9_-]/', '', $submissionUid);
    if ($safeUid === '') return [];
    $baseDir = rtrim(CSE_STORAGE_PATH, '/') . '/uploads/' . $safeUid;
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0750, true);
    }
    $paths = [];
    $map = [
        'selfie_upload'    => 'selfie',
        'nic_front_upload' => 'nic_front',
        'nic_back_upload'  => 'nic_back',
        'passport_upload'  => 'passport',
    ];
    foreach ($map as $fileKey => $baseName) {
        if (empty($_FILES[$fileKey]['tmp_name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            continue;
        }
        $ext = strtolower(pathinfo($_FILES[$fileKey]['name'] ?? '', PATHINFO_EXTENSION) ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $ext = 'jpg';
        }
        $dest = $baseDir . '/' . $baseName . '.' . $ext;
        if (@copy($_FILES[$fileKey]['tmp_name'], $dest)) {
            $paths[$baseName] = 'uploads/' . $safeUid . '/' . basename($dest);
            liveLog("Saved image to: $dest");
        }
    }
    return $paths;
}

function updateSubmissionImagesByUid(string $submissionUid, array $imagePaths): void {
    try {
        $pdo = getDb();
        $stmt = $pdo->prepare('UPDATE cds_submissions SET image_paths = ?, updated_at = NOW() WHERE submission_uid = ?');
        $stmt->execute([json_encode($imagePaths, JSON_UNESCAPED_UNICODE), $submissionUid]);
        if ($stmt->rowCount() > 0) {
            liveLog('Updated image paths in DB: submission_uid=' . $submissionUid);
        }
    } catch (Throwable $e) {
        liveLog('DB image update failed: ' . $e->getMessage(), 'error');
    }
}

/**
 * Validate a raw edit-link token and stamp last_used_at.
 *
 * The token is reusable until either (a) it expires, or (b) the underlying
 * submission flips to status=submitted_to_cse — at which point the status
 * check below rejects it permanently.
 *
 * Returns ['submission_id' => int, 'submission' => array{form_data, image_paths, submission_uid}]
 * on success, or null on any failure.
 */
function consumeEditTokenForClient(string $rawToken): ?array {
    if (strlen($rawToken) < 16) return null;
    $hash = hash('sha256', $rawToken);
    $pdo = getDb();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'SELECT t.id, t.submission_id, t.expires_at,'
            . ' s.submission_uid, s.form_data, s.image_paths, s.status'
            . ' FROM submission_edit_tokens t'
            . ' INNER JOIN cds_submissions s ON s.id = t.submission_id'
            . ' WHERE t.token_hash = ? FOR UPDATE'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $pdo->rollBack();
            return null;
        }
        if (strtotime($row['expires_at']) < time()) {
            $pdo->rollBack();
            return null;
        }
        if (!in_array($row['status'], ['pending_review', 'awaiting_edit'], true)) {
            $pdo->rollBack();
            return null;
        }
        $upd = $pdo->prepare('UPDATE submission_edit_tokens SET last_used_at = NOW() WHERE id = ?');
        $upd->execute([$row['id']]);
        $pdo->commit();
        return [
            'submission_id' => (int)$row['submission_id'],
            'submission' => [
                'submission_uid' => $row['submission_uid'],
                'form_data' => json_decode($row['form_data'] ?: '[]', true) ?: [],
                'image_paths' => json_decode($row['image_paths'] ?: '[]', true) ?: [],
                'status' => $row['status'],
            ],
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        liveLog('Token consume failed: ' . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * After CSE accepts the submission, lock the row and store the CSE account id.
 */
function markSubmissionSubmittedToCse(int $submissionId, string $cseAccountId, array $formData): void {
    try {
        $pdo = getDb();
        $stmt = $pdo->prepare(
            'UPDATE cds_submissions'
            . ' SET cse_account_id = ?, account_id = ?, form_data = ?,'
            . '     status = ?, submitted_to_cse_at = NOW(), admin_note = NULL,'
            . '     updated_at = NOW()'
            . ' WHERE id = ?'
        );
        $stmt->execute([
            $cseAccountId,
            $cseAccountId,
            json_encode($formData, JSON_UNESCAPED_UNICODE),
            'submitted_to_cse',
            $submissionId,
        ]);
        liveLog('Marked submission as submitted_to_cse: id=' . $submissionId . ' account=' . $cseAccountId);
    } catch (Throwable $e) {
        liveLog('Failed to mark submission as submitted_to_cse: ' . $e->getMessage(), 'error');
    }
}


// ==================== VALIDATION ====================
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


// ==================== LOGGING / IDS ====================
function liveLog($message, $level = 'info') {
    $logFile = defined('CSE_LOG_FILE') && CSE_LOG_FILE ? CSE_LOG_FILE : (CSE_STORAGE_PATH . '/api.log');
    if (!$logFile) return;
    $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
    if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0750, true);
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function generateUserId() {
    return (isset($_SERVER['REMOTE_ADDR']) ? str_replace('.', '', $_SERVER['REMOTE_ADDR']) : 'web')
        . '_' . date('YmdHis')
        . '_' . substr(md5(uniqid('', true)), 0, 6);
}
