<?php
/**
 * Supporting documents — shared helpers.
 *
 * These docs (utility bills, bank statements, TIN certs, custom client-named
 * categories, etc.) are stored on the server only. They are NEVER pushed to
 * CSE — `cse_uploadImages()` iterates a hardcoded whitelist of the four ID
 * keys (selfie / nic_front / nic_back / passport) on `image_paths`, and this
 * data lives in a SEPARATE column (`supporting_documents`). Do not wire any
 * CSE call against the structures below.
 *
 * Storage layout on disk:
 *   <CSE_STORAGE_PATH>/uploads/<submission_uid>/supporting/<safe-cat-key>/<random>.<ext>
 *
 * In-DB JSON shape:
 * {
 *   "categories": [
 *     {
 *       "id": "utility_bill",
 *       "label": "Utility Bill (Electricity / Water / Telecom)",
 *       "custom": false,
 *       "files": [
 *         {"name":"electricity_jan.pdf","path":"uploads/<uid>/supporting/utility_bill/<rand>.pdf",
 *          "mime":"application/pdf","size":234567,"uploaded_at":"2026-04-28 22:13:00","id":"<uniq>"}
 *       ]
 *     }
 *   ]
 * }
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/form_constants.php';

/** Top-level shape for an empty `supporting_documents` value. */
function supporting_docs_empty(): array {
    return ['categories' => []];
}

/**
 * Decode the JSON column into a sane array, normalising legacy / malformed shapes.
 */
function supporting_docs_normalize($raw): array {
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
    } elseif (is_array($raw)) {
        $decoded = $raw;
    } else {
        $decoded = null;
    }
    if (!is_array($decoded)) return supporting_docs_empty();
    if (!isset($decoded['categories']) || !is_array($decoded['categories'])) {
        return supporting_docs_empty();
    }
    $out = ['categories' => []];
    foreach ($decoded['categories'] as $cat) {
        if (!is_array($cat)) continue;
        $id = sanitize_supporting_category_key($cat['id'] ?? '');
        $label = sanitize_supporting_category_label($cat['label'] ?? $id);
        $custom = !empty($cat['custom']);
        $files = [];
        if (isset($cat['files']) && is_array($cat['files'])) {
            foreach ($cat['files'] as $f) {
                if (!is_array($f)) continue;
                if (empty($f['path'])) continue;
                $files[] = [
                    'id'          => isset($f['id']) ? (string)$f['id'] : substr(md5(($f['path'] ?? '') . microtime(true)), 0, 12),
                    'name'        => isset($f['name']) ? (string)$f['name'] : basename((string)$f['path']),
                    'path'        => (string)$f['path'],
                    'mime'        => isset($f['mime']) ? (string)$f['mime'] : '',
                    'size'        => isset($f['size']) ? (int)$f['size'] : 0,
                    'uploaded_at' => isset($f['uploaded_at']) ? (string)$f['uploaded_at'] : date('Y-m-d H:i:s'),
                ];
            }
        }
        $out['categories'][] = [
            'id'     => $id,
            'label'  => $label !== '' ? $label : $id,
            'custom' => (bool)$custom,
            'files'  => $files,
        ];
    }
    return $out;
}

/** Lookup a category by id; returns the array index or -1. */
function supporting_docs_find_index(array $docs, string $id): int {
    foreach ($docs['categories'] as $i => $cat) {
        if (($cat['id'] ?? '') === $id) return $i;
    }
    return -1;
}

/**
 * Validate one uploaded $_FILES entry against the supporting-doc rules.
 * Returns the resolved lowercase extension on success or null on failure
 * (and writes a reason into $reason).
 */
function supporting_docs_validate_upload(array $fileEntry, ?string &$reason = null): ?string {
    $reason = null;
    if (empty($fileEntry['tmp_name']) || ($fileEntry['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $reason = 'Upload error';
        return null;
    }
    $size = (int)($fileEntry['size'] ?? 0);
    if ($size <= 0 || $size > SUPPORTING_DOC_MAX_SIZE) {
        $reason = 'File exceeds 2MB limit';
        return null;
    }
    $ext = strtolower(pathinfo((string)($fileEntry['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, get_supporting_doc_allowed_exts(), true)) {
        $reason = 'Unsupported file type';
        return null;
    }
    // Detect mime from disk; do not trust client-supplied $fileEntry['type'].
    $detectedMime = function_exists('mime_content_type') ? @mime_content_type($fileEntry['tmp_name']) : null;
    if ($detectedMime && !in_array(strtolower($detectedMime), get_supporting_doc_allowed_mimes(), true)) {
        // Some servers report image/jpg vs image/jpeg; allow image/* for known image exts.
        $isImageExt = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
        $isPdfExt   = $ext === 'pdf';
        $mimeIsImage = strpos($detectedMime, 'image/') === 0;
        $mimeIsPdf   = $detectedMime === 'application/pdf';
        if (!(($isImageExt && $mimeIsImage) || ($isPdfExt && $mimeIsPdf))) {
            $reason = 'Unsupported file content';
            return null;
        }
    }
    return $ext;
}

/**
 * Persist one uploaded file under storage/uploads/<uid>/supporting/<catKey>/.
 * Returns the file descriptor to merge into the JSON column, or null on failure.
 */
function supporting_docs_save_one(string $submissionUid, string $catKey, array $fileEntry): ?array {
    $reason = null;
    $ext = supporting_docs_validate_upload($fileEntry, $reason);
    if ($ext === null) {
        if (function_exists('liveLog')) {
            liveLog('Supporting doc rejected (' . $catKey . '): ' . $reason . ' [' . ($fileEntry['name'] ?? '') . ']', 'error');
        }
        return null;
    }
    $safeUid = preg_replace('/[^a-zA-Z0-9_-]/', '', $submissionUid);
    $safeKey = sanitize_supporting_category_key($catKey);
    if ($safeUid === '' || $safeKey === '') return null;

    $relDir = 'uploads/' . $safeUid . '/supporting/' . $safeKey;
    $absDir = rtrim(CSE_STORAGE_PATH, '/') . '/' . $relDir;
    if (!is_dir($absDir) && !@mkdir($absDir, 0750, true) && !is_dir($absDir)) {
        return null;
    }
    $fileId = bin2hex(random_bytes(8));
    $relPath = $relDir . '/' . $fileId . '.' . $ext;
    $absPath = rtrim(CSE_STORAGE_PATH, '/') . '/' . $relPath;
    $copied = is_uploaded_file($fileEntry['tmp_name'])
        ? @move_uploaded_file($fileEntry['tmp_name'], $absPath)
        : @copy($fileEntry['tmp_name'], $absPath);
    if (!$copied) return null;

    $detectedMime = function_exists('mime_content_type') ? (@mime_content_type($absPath) ?: '') : '';
    return [
        'id'          => $fileId,
        'name'        => substr(preg_replace('/[\x00-\x1F\x7F]/u', '', (string)($fileEntry['name'] ?? ($fileId . '.' . $ext))), 0, 200),
        'path'        => $relPath,
        'mime'        => $detectedMime ?: ('application/octet-stream'),
        'size'        => (int)filesize($absPath),
        'uploaded_at' => date('Y-m-d H:i:s'),
    ];
}

/**
 * Apply a multipart upload of supporting docs:
 *  - $existing  – current `supporting_documents` value (already normalised).
 *  - $meta      – decoded `supporting_meta` JSON from the request body.
 *  - $files     – the relevant slice of $_FILES (whole array works fine).
 *  - $removeIds – list of file ids the user asked to delete.
 *
 * Returns the new normalised structure to persist to the DB.
 */
function supporting_docs_apply_request(string $submissionUid, array $existing, $meta, array $files, array $removeIds = []): array {
    $existing = supporting_docs_normalize($existing);
    $predefined = get_supporting_doc_types();

    // 1) Apply removals first (and unlink files on disk).
    if (!empty($removeIds)) {
        $removeSet = array_flip(array_map('strval', $removeIds));
        foreach ($existing['categories'] as $cIdx => &$cat) {
            $kept = [];
            foreach ($cat['files'] as $f) {
                $fid = (string)($f['id'] ?? '');
                if ($fid !== '' && isset($removeSet[$fid])) {
                    $abs = rtrim(CSE_STORAGE_PATH, '/') . '/' . ltrim((string)$f['path'], '/');
                    if (is_file($abs)) @unlink($abs);
                    continue;
                }
                $kept[] = $f;
            }
            $cat['files'] = $kept;
        }
        unset($cat);
    }

    // 2) Decode meta (allow either an array or a JSON string).
    if (is_string($meta)) {
        $meta = json_decode($meta, true);
    }
    if (!is_array($meta)) $meta = [];

    foreach ($meta as $entry) {
        if (!is_array($entry)) continue;
        $isCustom = !empty($entry['custom']);
        $rawLabel = sanitize_supporting_category_label($entry['label'] ?? '');
        $rawId    = (string)($entry['id'] ?? '');
        $fieldKey = (string)($entry['field'] ?? '');
        if ($fieldKey === '' || strpos($fieldKey, 'supporting_') !== 0) continue;

        // Resolve canonical category id + label.
        if ($isCustom) {
            $catId = $rawId !== '' ? sanitize_supporting_category_key($rawId)
                                   : sanitize_supporting_category_key('custom_' . substr(md5($rawLabel . microtime(true)), 0, 10));
            $catLabel = $rawLabel !== '' ? $rawLabel : 'Other Document';
        } else {
            $catId = sanitize_supporting_category_key($rawId);
            if (!isset($predefined[$catId])) continue;
            $catLabel = $predefined[$catId];
        }

        // Pull this category's slice from $_FILES (PHP normalises multi-file
        // inputs into parallel arrays; iterate by index).
        if (!isset($files[$fieldKey]) || !is_array($files[$fieldKey])) continue;
        $slice = $files[$fieldKey];
        if (!isset($slice['name']) || !is_array($slice['name'])) continue;

        $newFiles = [];
        $count = count($slice['name']);
        for ($i = 0; $i < $count; $i++) {
            $oneFile = [
                'name'     => $slice['name'][$i] ?? '',
                'type'     => $slice['type'][$i] ?? '',
                'tmp_name' => $slice['tmp_name'][$i] ?? '',
                'error'    => $slice['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $slice['size'][$i] ?? 0,
            ];
            $saved = supporting_docs_save_one($submissionUid, $catId, $oneFile);
            if ($saved) $newFiles[] = $saved;
        }

        if (empty($newFiles)) continue;

        // Merge into existing category (or create one).
        $idx = supporting_docs_find_index($existing, $catId);
        if ($idx === -1) {
            $existing['categories'][] = [
                'id'     => $catId,
                'label'  => $catLabel,
                'custom' => (bool)$isCustom,
                'files'  => $newFiles,
            ];
        } else {
            // Refresh label for custom categories so renames propagate.
            if ($isCustom && $catLabel !== '') {
                $existing['categories'][$idx]['label'] = $catLabel;
            }
            $existing['categories'][$idx]['files'] = array_merge(
                $existing['categories'][$idx]['files'],
                $newFiles
            );
        }
    }

    // 3) Drop categories that ended up with zero files and aren't predefined.
    $existing['categories'] = array_values(array_filter($existing['categories'], function ($cat) use ($predefined) {
        if (!empty($cat['files'])) return true;
        if (!empty($cat['custom'])) return false;
        // Keep predefined empties out of the saved JSON to keep it tidy.
        return false;
    }));

    return $existing;
}

/** Total file count across every category. */
function supporting_docs_count(array $docs): int {
    $n = 0;
    foreach (($docs['categories'] ?? []) as $cat) {
        $n += isset($cat['files']) && is_array($cat['files']) ? count($cat['files']) : 0;
    }
    return $n;
}

/** Pretty file size. */
function supporting_docs_human_size(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1024 / 1024, 2) . ' MB';
}
