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
