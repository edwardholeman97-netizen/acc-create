<?php
/**
 * Form dropdown constants – shared by main form (index.php) and admin edit.
 * Single source of truth for hardcoded options.
 */

/** Identification Proof (ID type): value => label */
function get_form_id_proof_options() {
    return ['NIC' => 'NIC', 'Passport' => 'Passport'];
}

/** Residential Address Status Description */
function get_form_res_address_status_options() {
    return [
        '1' => '1 - Owner',
        '2' => '2 - With parents',
        '3' => '3 - Lease / Rent',
        '4' => "4 - Friend's / Relative's",
        '5' => '5 - Board / Lodging',
        '6' => '6 - Official',
    ];
}

/** Employment Status */
function get_form_employment_status_options() {
    return [
        'Y' => 'Employed',
        'N' => 'Unemployed',
        'S' => 'Self-Employed',
        'T' => 'Student',
        'R' => 'Retired',
    ];
}

/** Expected Value of Investment */
function get_form_exp_value_options() {
    return [
        '1' => 'Less than LKR 100,000',
        '2' => 'LKR 100,000 - 500,000',
        '3' => 'LKR 500,000 - 1,000,000',
        '4' => 'LKR 1,000,000 - 5,000,000',
        '5' => 'More than LKR 5,000,000',
    ];
}

/**
 * Permanently locked field keys for the CLIENT edit-link flow.
 *
 * These fields are captured ONCE on the very first client submission and can
 * never be changed by the client via the edit link. Used by:
 *  - api.php client-resubmit branch (server-side overwrite from DB)
 *  - edit-submission.php (UI render — adds lock icon + readonly/disabled)
 *
 * Note: admin/edit.php maintains its OWN `locked` flags per field, so the
 * admin lock policy is independent of this list. Bank details, for example,
 * are unlocked here (clients can correct them via the edit link) but remain
 * locked in admin/edit.php by that file's own configuration.
 */
function get_form_locked_field_keys() {
    return [
        'MobileNo',
        'TelphoneNo',
        'Email',
        'NicNo',
        'PassportNo',
        'InvestorId',
    ];
}

/**
 * Predefined supporting-document categories shown on the final form step.
 *
 * These are OPTIONAL extra documents (utility bills, bank statements, etc.)
 * stored on the server only. They are NEVER sent to CSE.
 *
 * Returns: id => label
 */
function get_supporting_doc_types() {
    return [
        'utility_bill'          => 'Utility Bill (Electricity / Water / Telecom)',
        'bank_passbook'         => 'Bank Passbook / Bank Statement',
        'tin_certificate'       => 'TIN Certificate',
        'income_proof'          => 'Proof of Income / Employment (Recent Pay Slip)',
        'business_registration' => 'Business Registration (BR) Copy',
        'visa_passport_copy'    => 'Visa / Foreign Passport Copy',
    ];
}

/** Allowed MIME types for supporting documents. */
function get_supporting_doc_allowed_mimes() {
    return [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/pjpeg',
        'image/png',
        'image/x-png',
        'image/gif',
        'image/webp',
    ];
}

/** Allowed file extensions for supporting documents (lowercase, no dot). */
function get_supporting_doc_allowed_exts() {
    return ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
}

/** Per-file size cap for supporting documents (bytes). Matches the ID-photo cap. */
if (!defined('SUPPORTING_DOC_MAX_SIZE')) {
    define('SUPPORTING_DOC_MAX_SIZE', 2 * 1024 * 1024);
}

/**
 * Sanitize a category id (predefined key or generated custom id) into a
 * filesystem-safe slug. Strips everything except [a-zA-Z0-9_-], collapses
 * length, and falls back to a hash if empty.
 */
function sanitize_supporting_category_key($raw) {
    $raw = (string)$raw;
    $clean = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw);
    $clean = substr($clean, 0, 64);
    if ($clean === '' || $clean === null) {
        $clean = 'cat_' . substr(md5($raw . microtime(true)), 0, 12);
    }
    return $clean;
}

/**
 * Sanitize a human-supplied category label (e.g. for a custom doc type).
 * Trims, collapses whitespace, strips control chars, hard caps to 80 chars.
 */
function sanitize_supporting_category_label($raw) {
    $raw = (string)$raw;
    $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw);
    $clean = preg_replace('/\s+/u', ' ', $clean);
    $clean = trim($clean);
    return mb_substr($clean, 0, 80);
}

/** Source of Funds */
function get_form_source_of_funds_options() {
    return [
        '1' => 'Salary/Profit Income',
        '2' => 'Investment Proceeds/Savings',
        '3' => 'Sales and Business Turnover',
        '4' => 'Contract Proceeds',
        '5' => 'Sales of Property/Assets',
        '6' => 'Gifts',
        '7' => 'Donations/Charities',
        '8' => 'Commission Income',
        '9' => 'Family Remittance',
        '10' => 'Export proceeds',
        '11' => 'Membership contribution',
    ];
}
