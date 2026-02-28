<?php require_once __DIR__ . '/includes/form_constants.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDS Account Opening</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>



<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-user-plus"></i> CDS Account Opening</h1>
        <p>Complete the form below to open your Central Depository System (CDS) account</p>
    </div>
    
    <div class="progress-bar">
        <div class="progress" id="form-progress"></div>
    </div>

    <!-- Step Navigation -->
    <div class="step-navigation">
        <div class="step active" data-step="1">
            <span class="step-number">1</span>
            <span class="step-title">Personal Info</span>
        </div>
        <div class="step" data-step="2">
            <span class="step-number">2</span>
            <span class="step-title">Identification</span>
        </div>
        <div class="step" data-step="3">
            <span class="step-number">3</span>
            <span class="step-title">Investment</span>
        </div>
        <div class="step" data-step="4">
            <span class="step-number">4</span>
            <span class="step-title">Address</span>
        </div>
        <div class="step" data-step="5">
            <span class="step-number">5</span>
            <span class="step-title">Employment</span>
        </div>
        <div class="step" data-step="6">
            <span class="step-number">6</span>
            <span class="step-title">Bank & Funds</span>
        </div>
        <div class="step" data-step="7">
            <span class="step-number">7</span>
            <span class="step-title">Documents</span>
        </div>
    </div>

    <div class="form-container">
        <div class="status-message" id="status-message" role="alert" aria-live="polite"></div>
        <form id="cdsAccountForm">
            <!-- Form Steps Container -->
            <div class="form-steps-container">
                <!-- Step 1: Personal Information -->
                <div class="form-step active" id="step-1">
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-user"></i> Personal Information</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="Title" class="required">Title</label>
                                <select id="Title" name="Title" required>
                                    <option value="">Select Title</option>
                                    <!-- Populated from /api/OtherServices/GetTitle (doc); fallback options below if API unused -->
                                    <option value="Mr">Mr</option>
                                    <option value="Mrs">Mrs</option>
                                    <option value="Miss">Miss</option>
                                    <option value="Dr">Dr</option>
                                    <option value="Prof">Prof</option>
                                </select>
                                <div class="error-message">Please select a title</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="Initials" class="required">Initials</label>
                                <input type="text" id="Initials" name="Initials" maxlength="15" required>
                                <div class="error-message">Please enter initials (max 15 characters)</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="Surname" class="required">Surname</label>
                                <input type="text" id="Surname" name="Surname" maxlength="50" required>
                                <div class="error-message">Please enter surname</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="NameDenoInitials" class="required">Full Name (Denoted by Initials)</label>
                                <input type="text" id="NameDenoInitials" name="NameDenoInitials" maxlength="160" required>
                                <div class="form-note">Full name represented by initials</div>
                                <div class="error-message">Please enter full name</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="MobileNo" class="required">Mobile Number</label>
                                <input type="tel" id="MobileNo" name="MobileNo" maxlength="16" required pattern="[0-9+]{10,16}">
                                <div class="form-note">Format: +94123456789 or 0123456789</div>
                                <div class="error-message">Please enter a valid mobile number (10-16 digits)</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="TelphoneNo">Telephone Number</label>
                                <input type="tel" id="TelphoneNo" name="TelphoneNo" maxlength="16">
                            </div>
                            
                            <div class="form-group">
                                <label for="Email" class="required">Email Address</label>
                                <input type="email" id="Email" name="Email" maxlength="100" required>
                                <div class="error-message">Please enter a valid email address</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="DateOfBirthday" class="required">Date of Birth</label>
                                <input type="date" id="DateOfBirthday" name="DateOfBirthday" required>
                                <div class="error-message">Please enter a valid date of birth</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="Gender" class="required">Gender</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="gender_m" name="Gender" value="M" required>
                                        <label for="gender_m">Male</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="gender_f" name="Gender" value="F" required>
                                        <label for="gender_f">Female</label>
                                    </div>
                                </div>
                                <div class="error-message">Please select gender</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Identification Details -->
                <div class="form-step" id="step-2">
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-id-card"></i> Identification Details</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="IdentificationProof" class="required">Identification Proof</label>
                                <select id="IdentificationProof" name="IdentificationProof" required>
                                    <option value="">Select ID Type</option>
                                    <?php foreach (get_form_id_proof_options() as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="error-message">Please select identification proof type</div>
                            </div>
                            
                            <div class="form-group" id="nic-field">
                                <label for="NicNo" class="required">NIC No</label>
                                <input type="text" required id="NicNo" name="NicNo" maxlength="12" pattern="[0-9]{9,12}[VX]?" title="Enter valid NIC number (e.g., 123456789V)">
                                <div class="form-note">Format: 123456789V or 200012345678</div>
                                <div class="error-message">Please enter valid NIC number</div>
                            </div>
                            
                            <div class="form-group" id="passport-field" style="display: none;">
                                <label for="PassportNo" class="required">Passport No</label>
                                <input type="text" id="PassportNo" name="PassportNo" maxlength="50">
                                <div class="error-message">Please enter passport number</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" id="passport-exp-field" style="display: none;">
                                <label for="PassportExpDate" class="required">Passport Expiry Date</label>
                                <input type="date" id="PassportExpDate" name="PassportExpDate">
                                <div class="error-message">Please enter passport expiry date</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Investment Information -->
                <div class="form-step" id="step-3">
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-chart-line"></i> Investment Information</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="BrokerFirm" class="required">Stock Broker Firm</label>
                                <select id="BrokerFirm" name="BrokerFirm" required>
                                    <option value="">Select Broker</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="error-message">Please select broker firm</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="ExitCDSAccount">Existing CDS Account</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="exit_cds_y" name="ExitCDSAccount" value="Y">
                                        <label for="exit_cds_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="exit_cds_n" name="ExitCDSAccount" value="N">
                                        <label for="exit_cds_n">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" id="cds-account-field" style="display: none;">
                                <label for="CDSAccountNo">CDS Account Number</label>
                                <input type="text" id="CDSAccountNo" name="CDSAccountNo" maxlength="20">
                            </div>
                            
                            <div class="form-group">
                                <label for="TinNo">TIN Number</label>
                                <input type="text" id="TinNo" name="TinNo" maxlength="20">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="InvestorId">Investor / Advisor</label>
                                <select id="InvestorId" name="InvestorId">
                                    <option value="">None</option>
                                    <!-- Populated from /api/OtherServices/GetInvestAdvisors (doc) -->
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="InvestmentOb">Investment Objectives</label>
                                <textarea id="InvestmentOb" name="InvestmentOb" maxlength="100" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="InvestmentStrategy">Investment Strategy</label>
                                <textarea id="InvestmentStrategy" name="InvestmentStrategy" maxlength="200" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 4: Address Information -->
                <div class="form-step" id="step-4">
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-home"></i> Address Information</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ResAddressStatus" class="required">Residential Address Status</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="res_addr_y" name="ResAddressStatus" value="Y" required>
                                        <label for="res_addr_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="res_addr_n" name="ResAddressStatus" value="N" required>
                                        <label for="res_addr_n">No</label>
                                    </div>
                                </div>
                                <div class="error-message">Please select residential address status</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="ResAddressStatusDesc" class="required">Residential Address Status Description</label>
                                <select id="ResAddressStatusDesc" name="ResAddressStatusDesc" required>
                                    <option value="">Select</option>
                                    <?php foreach (get_form_res_address_status_options() as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="error-message">Please select status description</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ResAddressLine01" class="required">Address Line 1</label>
                                <input type="text" id="ResAddressLine01" name="ResAddressLine01" maxlength="30" required>
                                <div class="error-message">Please enter address line 1</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="ResAddressLine02">Address Line 2</label>
                                <input type="text" id="ResAddressLine02" name="ResAddressLine02" maxlength="30">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ResAddressLine03">Address Line 3</label>
                                <input type="text" id="ResAddressLine03" name="ResAddressLine03" maxlength="15">
                            </div>
                            
                            <div class="form-group">
                                <label for="ResAddressTown" class="required">Town</label>
                                <input type="text" id="ResAddressTown" name="ResAddressTown" maxlength="15" required>
                                <div class="error-message">Please enter Town</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ResAddressDistrict" class="required">District</label>
                                <select id="ResAddressDistrict" name="ResAddressDistrict" required>
                                    <option value="">Select District</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="error-message">Please select district</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="Country" class="required">Country</label>
                                <select id="Country" name="Country" required>
                                    <option value="">Select Country</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="error-message">Please select country</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="CountryOfResidency" class="required">Country of Residency</label>
                                <select id="CountryOfResidency" name="CountryOfResidency" required>
                                    <option value="">Select Country</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="error-message">Please select country of residency</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="Nationality" class="required">Nationality</label>
                                <select id="Nationality" name="Nationality" required>
                                    <option value="">Select Nationality</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="error-message">Please select nationality</div>
                            </div>
                        </div>
                        
                        <!-- Correspondence Address (optional) -->
                        <h3 style="margin: 25px 0 15px; color: #2c3e50;">Correspondence Address (optional)</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="CorrAddressStatus">Correspondence Address Same as Residential?</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="corr_addr_y" name="CorrAddressStatus" value="Y">
                                        <label for="corr_addr_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="corr_addr_n" name="CorrAddressStatus" value="N">
                                        <label for="corr_addr_n">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="CorrAddressLine01">Corr Address Line 1</label>
                                <input type="text" id="CorrAddressLine01" name="CorrAddressLine01" maxlength="30">
                            </div>
                            <div class="form-group">
                                <label for="CorrAddressLine02">Corr Address Line 2</label>
                                <input type="text" id="CorrAddressLine02" name="CorrAddressLine02" maxlength="30">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="CorrAddressLine03">Corr Address Line 3</label>
                                <input type="text" id="CorrAddressLine03" name="CorrAddressLine03" maxlength="15">
                            </div>
                            <div class="form-group">
                                <label for="CorrAddressTown">Corr Address Town</label>
                                <input type="text" id="CorrAddressTown" name="CorrAddressTown" maxlength="15">
                            </div>
                            <div class="form-group">
                                <label for="CorrAddressDistrict">Corr Address District</label>
                                <input type="text" id="CorrAddressDistrict" name="CorrAddressDistrict" maxlength="10">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 5: Employment Information -->
                <div class="form-step" id="step-5">
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-briefcase"></i> Employment Information</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="EmployeStatus" class="required">Employment Status</label>
                                <select id="EmployeStatus" name="EmployeStatus" required>
                                    <option value="">Select Status</option>
                                    <?php foreach (get_form_employment_status_options() as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="error-message">Please select employment status</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="Occupation">Occupation</label>
                                <input type="text" id="Occupation" name="Occupation" maxlength="50">
                            </div>
                        </div>
                        
                        <div class="conditional-field" id="employment-details">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="NameOfEmployer">Name of Employer</label>
                                    <input type="text" id="NameOfEmployer" name="NameOfEmployer" maxlength="100">
                                </div>
                                
                                <div class="form-group">
                                    <label for="AddressOfEmployer">Address of Employer</label>
                                    <input type="text" id="AddressOfEmployer" name="AddressOfEmployer" maxlength="150">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="OfficePhoneNo">Office Phone Number</label>
                                    <input type="tel" id="OfficePhoneNo" name="OfficePhoneNo" maxlength="16">
                                </div>
                                
                                <div class="form-group">
                                    <label for="OfficeEmail">Office Email</label>
                                    <input type="email" id="OfficeEmail" name="OfficeEmail" maxlength="100">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="EmployeeComment">Employee Comment</label>
                                    <textarea id="EmployeeComment" name="EmployeeComment" maxlength="500" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Business (optional) -->
                        <h3 style="margin: 25px 0 15px; color: #2c3e50;">Business (optional)</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="NameOfBusiness">Name of Business</label>
                                <input type="text" id="NameOfBusiness" name="NameOfBusiness" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="AddressOfBusiness">Address of Business</label>
                                <input type="text" id="AddressOfBusiness" name="AddressOfBusiness" maxlength="150">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="OtherConnBusinessStatus">Other Connected Business Status</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="other_biz_y" name="OtherConnBusinessStatus" value="Y">
                                        <label for="other_biz_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="other_biz_n" name="OtherConnBusinessStatus" value="N">
                                        <label for="other_biz_n">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="OtherConnBusinessDesc">Other Connected Business Description</label>
                                <input type="text" id="OtherConnBusinessDesc" name="OtherConnBusinessDesc" maxlength="200">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 6: Bank & Source of Funds -->
                <div class="form-step" id="step-6">
                    <!-- Bank Account Information -->
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-university"></i> Bank Account Information</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="BankAccountNo" class="required">Bank Account Number</label>
                                <input type="text" id="BankAccountNo" name="BankAccountNo" maxlength="12" required>
                                <div class="error-message">Please enter bank account number</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="BankCode" class="required">Bank Code</label>
                                <select id="BankCode" name="BankCode" required>
                                    <option value="">Select Bank</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="error-message">Please select bank</div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="BankBranch" class="required">Bank Branch</label>
                                <select id="BankBranch" name="BankBranch" required>
                                    <option value="">Select Branch</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="error-message">Please select bank branch</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="BankAccountType" class="required">Bank Account Type</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="account_type_i" name="BankAccountType" value="I" required>
                                        <label for="account_type_i">Individual (IIA)</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="account_type_c" name="BankAccountType" value="C" required>
                                        <label for="account_type_c">Corporate (CTRA)</label>
                                    </div>
                                </div>
                                <div class="error-message">Please select account type</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Source of Funds -->
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-money-bill-wave"></i> Source of Funds</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ExpValueInvestment" class="required">Expected Value of Investment</label>
                                <select id="ExpValueInvestment" name="ExpValueInvestment" required>
                                    <option value="">Select Range</option>
                                    <?php foreach (get_form_exp_value_options() as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="error-message">Please select expected investment value</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="SourseOfFund">Source of Funds</label>
                                <select id="SourseOfFund" name="SourseOfFund">
                                    <option value="">Select Source</option>
                                    <?php foreach (get_form_source_of_funds_options() as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 7: Compliance & Documents -->
                <div class="form-step" id="step-7">
                    <!-- PEP and Compliance -->
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-shield-alt"></i> Compliance Information</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="IsPEP" class="required">Politically Exposed Person (PEP)</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="pep_y" name="IsPEP" value="Y" required>
                                        <label for="pep_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="pep_n" name="IsPEP" value="N" required>
                                        <label for="pep_n">No</label>
                                    </div>
                                </div>
                                <div class="error-message">Please select PEP status</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="LitigationStatus" class="required">Litigation Status</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="litigation_y" name="LitigationStatus" value="Y" required>
                                        <label for="litigation_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="litigation_n" name="LitigationStatus" value="N" required>
                                        <label for="litigation_n">No</label>
                                    </div>
                                </div>
                                <div class="error-message">Please select litigation status</div>
                            </div>
                        </div>
                        
                        <!-- PEP Questions (Conditional) -->
                        <div class="conditional-field" id="pep-questions">
                            <h3>PEP Details</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="PEP_Q1" class="required">PEP Q1</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q1_y" name="PEP_Q1" value="Y" required>
                                            <label for="pep_q1_y">Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q1_n" name="PEP_Q1" value="N" required>
                                            <label for="pep_q1_n">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="PEP_Q1_Details">PEP Q1 Details (optional)</label>
                                    <input type="text" id="PEP_Q1_Details" name="PEP_Q1_Details" maxlength="100">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="PEP_Q2" class="required">PEP Q2</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q2_y" name="PEP_Q2" value="Y" required>
                                            <label for="pep_q2_y">Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q2_n" name="PEP_Q2" value="N" required>
                                            <label for="pep_q2_n">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="PEP_Q2_Details">PEP Q2 Details (optional)</label>
                                    <input type="text" id="PEP_Q2_Details" name="PEP_Q2_Details" maxlength="100">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="PEP_Q3" class="required">PEP Q3</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q3_y" name="PEP_Q3" value="Y" required>
                                            <label for="pep_q3_y">Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q3_n" name="PEP_Q3" value="N" required>
                                            <label for="pep_q3_n">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="PEP_Q3_Details">PEP Q3 Details (optional)</label>
                                    <input type="text" id="PEP_Q3_Details" name="PEP_Q3_Details" maxlength="100">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="PEP_Q4" class="required">PEP Q4</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q4_y" name="PEP_Q4" value="Y" required>
                                            <label for="pep_q4_y">Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q4_n" name="PEP_Q4" value="N" required>
                                            <label for="pep_q4_n">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="PEP_Q4_Details">PEP Q4 Details (optional)</label>
                                    <input type="text" id="PEP_Q4_Details" name="PEP_Q4_Details" maxlength="100">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Litigation Details (Conditional) -->
                        <div class="conditional-field" id="litigation-details">
                            <div class="form-group">
                                <label for="LitigationDetails">Litigation Details</label>
                                <textarea id="LitigationDetails" name="LitigationDetails" maxlength="100" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <!-- FATCA / USA / Dual Citizenship -->
                        <h3 style="margin: 25px 0 15px; color: #2c3e50;">FATCA / USA & Dual Citizenship</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="UsaPersonStatus">USA Person Status</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="usa_person_y" name="UsaPersonStatus" value="Y">
                                        <label for="usa_person_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="usa_person_n" name="UsaPersonStatus" value="N">
                                        <label for="usa_person_n">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="UsaTaxIdentificationNo">USA Tax Identification No</label>
                                <input type="text" id="UsaTaxIdentificationNo" name="UsaTaxIdentificationNo" maxlength="50">
                            </div>
                            <div class="form-group">
                                <label for="FactaDeclaration">FACTA Declaration</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="facta_y" name="FactaDeclaration" value="Y">
                                        <label for="facta_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="facta_n" name="FactaDeclaration" value="N">
                                        <label for="facta_n">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="DualCitizenship">Dual Citizenship</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="dual_cit_y" name="DualCitizenship" value="Y">
                                        <label for="dual_cit_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="dual_cit_n" name="DualCitizenship" value="N">
                                        <label for="dual_cit_n">No</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="DualCitizenCountry">Dual Citizen Country</label>
                                <input type="text" id="DualCitizenCountry" name="DualCitizenCountry" maxlength="10">
                            </div>
                            <div class="form-group">
                                <label for="DualCitizenPassport">Dual Citizen Passport No</label>
                                <input type="text" id="DualCitizenPassport" name="DualCitizenPassport" maxlength="50">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="IsLKPassport">Is LK Passport?</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="lk_passport_y" name="IsLKPassport" value="Y">
                                        <label for="lk_passport_y">Yes</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="lk_passport_n" name="IsLKPassport" value="N">
                                        <label for="lk_passport_n">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Upload Section -->
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-images"></i> Document Upload</h2>
                        <div class="upload-section">
                            <p>Please upload the following documents (Max 2MB each, JPG/PNG only):</p>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="selfie_upload" class="required">Selfie Photo</label>
                                    <input type="file" id="selfie_upload" name="selfie_upload" accept="image/jpeg,image/png">
                                    <div class="error-message">Please upload selfie photo</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nic_front_upload" class="required">NIC Front</label>
                                    <input type="file" id="nic_front_upload" name="nic_front_upload" accept="image/jpeg,image/png">
                                    <div class="error-message">Please upload NIC front photo</div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nic_back_upload" class="required">NIC Back</label>
                                    <input type="file" id="nic_back_upload" name="nic_back_upload" accept="image/jpeg,image/png">
                                    <div class="error-message">Please upload NIC back photo</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="passport_upload">Passport (if applicable)</label>
                                    <input type="file" id="passport_upload" name="passport_upload" accept="image/jpeg,image/png">
                                </div>
                            </div>
                            
                            <div class="preview-container" id="image-previews"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Information (Hidden) -->
            <input type="hidden" id="ClientType" name="ClientType" value="FI">
            <input type="hidden" id="Residency" name="Residency" value="R">
            <input type="hidden" id="ApiUser" name="ApiUser" value="DIALOG">
            <input type="hidden" id="Status" name="Status" value="1">
            <input type="hidden" id="EnterUser" name="EnterUser" value="SYSTEM">
            <input type="hidden" id="EnterDate" name="EnterDate">
            <input type="hidden" id="UserID" name="UserID">
            <input type="hidden" id="ApiRefNo" name="ApiRefNo">
            
            <!-- Navigation Buttons -->
            <div class="step-buttons">
                <button type="button" class="btn btn-prev" id="prev-btn">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button type="button" class="btn btn-next" id="next-btn">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
                <button type="button" class="btn btn-submit" id="submit-btn" style="display: none;">
                    <span class="btn-text"><i class="fas fa-paper-plane"></i> Submit Application</span>
                    <span class="btn-loader" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Submitting...</span>
                </button>
            </div>
        </form>
    </div>
</div>















    <script>
        // ==================== GLOBAL VARIABLES ====================
        let currentStep = 1;
        const totalSteps = 7;
        const dropdownCache = {};

        // ==================== INITIALIZATION ====================
        document.addEventListener('DOMContentLoaded', function() {
            initStepwiseForm();
            initEnhancedDropdowns();
            loadDynamicData();
            setupFormValidation();
            setupConditionalFields();
            setupDateDefaults();
            updateProgressBar();
        });

        // ==================== STEPWISE FORM FUNCTIONS ====================
        function initStepwiseForm() {
            // Wrap form sections in step containers
            wrapFormSectionsInSteps();

            // Show first step
            showStep(1);

            // Next button click
            document.getElementById('next-btn').addEventListener('click', function() {
                if (validateCurrentStep()) {
                    nextStep();
                }
            });

            // Previous button click
            document.getElementById('prev-btn').addEventListener('click', function() {
                prevStep();
            });

            // Step navigation click
            document.querySelectorAll('.step').forEach(step => {
                step.addEventListener('click', function() {
                    const stepNum = parseInt(this.getAttribute('data-step'));
                    if (stepNum < currentStep) {
                        goToStep(stepNum);
                    }
                });
            });

            // Submit button
            document.getElementById('submit-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (validateCurrentStep()) {
                    submitForm();
                }
            });
        }

        function wrapFormSectionsInSteps() {
            const formContainer = document.querySelector('.form-container');
            const formSections = formContainer.querySelectorAll('.form-section');
            const stepsContainer = document.createElement('div');
            stepsContainer.className = 'form-steps-container';

            // Define step mapping
            const stepMapping = [{
                    title: 'Personal Information',
                    sections: [0]
                },
                {
                    title: 'Identification Details',
                    sections: [1]
                },
                {
                    title: 'Investment Information',
                    sections: [2]
                },
                {
                    title: 'Address Information',
                    sections: [3]
                },
                {
                    title: 'Employment Information',
                    sections: [4]
                },
                {
                    title: 'Bank Information',
                    sections: [5]
                },
                {
                    title: 'Compliance & Documents',
                    sections: [6, 7, 8]
                } // Compliance, Source of Funds, Documents
            ];

            // Create step containers
            stepMapping.forEach((step, index) => {
                const stepDiv = document.createElement('div');
                stepDiv.className = 'form-step';
                stepDiv.id = `step-${index + 1}`;

                step.sections.forEach(sectionIndex => {
                    if (formSections[sectionIndex]) {
                        stepDiv.appendChild(formSections[sectionIndex].cloneNode(true));
                    }
                });

                stepsContainer.appendChild(stepDiv);
            });

            // Replace form content with steps
            const form = document.getElementById('cdsAccountForm');
            form.innerHTML = '';
            form.appendChild(stepsContainer);

            // Add step buttons container
            const stepButtons = document.createElement('div');
            stepButtons.className = 'step-buttons';
            stepButtons.innerHTML = `
        <button type="button" class="btn btn-prev" id="prev-btn">
            <i class="fas fa-arrow-left"></i> Previous
        </button>
        <button type="button" class="btn btn-next" id="next-btn">
            Next <i class="fas fa-arrow-right"></i>
        </button>
        <button type="button" class="btn btn-submit" id="submit-btn" style="display: none;">
            <span class="btn-text"><i class="fas fa-paper-plane"></i> Submit Application</span>
            <span class="btn-loader" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Submitting...</span>
        </button>
    `;
            form.appendChild(stepButtons);
        }

        function showStep(stepNumber) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });

            // Show current step
            const currentStepElement = document.getElementById('step-' + stepNumber);
            if (currentStepElement) {
                currentStepElement.classList.add('active');
            }

            // When step 6 (Bank) is shown, load bank branches if a bank is already selected
            if (stepNumber === 6) {
                const bankCodeEl = document.getElementById('BankCode');
                const branchSelect = document.getElementById('BankBranch');
                if (bankCodeEl && branchSelect && bankCodeEl.value && branchSelect.options.length <= 1) {
                    loadBankBranches();
                }
            }

            // Update navigation
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
                if (parseInt(step.getAttribute('data-step')) === stepNumber) {
                    step.classList.add('active');
                }
            });

            // Update buttons
            document.getElementById('prev-btn').style.display = stepNumber === 1 ? 'none' : 'inline-block';
            document.getElementById('next-btn').style.display = stepNumber === totalSteps ? 'none' : 'inline-block';
            document.getElementById('submit-btn').style.display = stepNumber === totalSteps ? 'inline-block' : 'none';

            // Update progress bar
            updateProgressBar();
        }

        function validateCurrentStep() {
            const currentStepElement = document.getElementById('step-' + currentStep);
            const requiredFields = currentStepElement.querySelectorAll('[required]');
            let isValid = true;

            // Clear previous errors in this step
            currentStepElement.querySelectorAll('.field-error').forEach(field => {
                field.classList.remove('field-error');
            });

            // Validate each required field
            const idType = document.getElementById('IdentificationProof')?.value;
            requiredFields.forEach(field => {
                // Skip NicNo when Passport is selected; skip PassportNo when NIC is selected
                if (currentStep === 2 && field.id === 'NicNo' && idType === 'Passport') return;
                if (currentStep === 2 && field.id === 'PassportNo' && idType === 'NIC') return;
                if (!validateField(field)) {
                    isValid = false;
                }
            });

            // Special validation for step 2 (Identification)
            if (currentStep === 2) {
                if (idType === 'NIC') {
                    const nicField = document.getElementById('NicNo');
                    if (nicField.value && !isValidNIC(nicField.value)) {
                        showFieldError(nicField, 'Please enter valid NIC number');
                        isValid = false;
                    }
                } else if (idType === 'Passport') {
                    const passportField = document.getElementById('PassportNo');
                    if (!passportField || !passportField.value.trim()) {
                        if (passportField) showFieldError(passportField, 'Passport number is required');
                        isValid = false;
                    }
                    const passportExpField = document.getElementById('PassportExpDate');
                    if (!passportExpField || !passportExpField.value.trim()) {
                        if (passportExpField) showFieldError(passportExpField, 'Please enter passport expiry date');
                        isValid = false;
                    }
                }
            }

            // Step 7 (Documents): validate file size (2MB) and type (JPG/PNG)
            if (currentStep === totalSteps) {
                const MAX_SIZE = 2 * 1024 * 1024; // 2MB
                const ALLOWED_TYPES = ['image/jpeg', 'image/png'];
                const fileInputs = ['selfie_upload', 'nic_front_upload', 'nic_back_upload', 'passport_upload'];
                fileInputs.forEach(name => {
                    const input = document.getElementById(name) || document.querySelector('[name="' + name + '"]');
                    if (!input || !input.files || !input.files[0]) return;
                    const file = input.files[0];
                    if (file.size > MAX_SIZE) {
                        showFieldError(input, 'File size must be less than 2MB');
                        isValid = false;
                    } else if (!ALLOWED_TYPES.includes(file.type)) {
                        showFieldError(input, 'Only JPG and PNG files are allowed');
                        isValid = false;
                    }
                });
            }

            if (!isValid) {
                scrollToFirstError(currentStepElement);
            }

            return isValid;
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }

        function goToStep(stepNumber) {
            if (stepNumber >= 1 && stepNumber <= totalSteps && stepNumber < currentStep) {
                currentStep = stepNumber;
                showStep(currentStep);
            }
        }

        // ==================== ENHANCED DROPDOWNS ====================
        function initEnhancedDropdowns() {
            // Convert regular selects to enhanced dropdowns
            const dropdownSelects = [
                'BrokerFirm',
                'ResAddressDistrict',
                'Country',
                'CountryOfResidency',
                'Nationality',
                'BankCode',
                'BankBranch'
            ];

            dropdownSelects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                   // convertToEnhancedDropdown(select);
                }
            });
        }

        function convertToEnhancedDropdown(selectElement) {
            const wrapper = document.createElement('div');
            wrapper.className = 'enhanced-dropdown';
            wrapper.id = selectElement.id + '-wrapper';

            const dropdownDisplay = document.createElement('div');
            dropdownDisplay.className = 'searchable-dropdown';
            dropdownDisplay.textContent = selectElement.options[0] ? selectElement.options[0].text : 'Select...';

            const dropdownOptions = document.createElement('div');
            dropdownOptions.className = 'dropdown-options';

            const searchInput = document.createElement('div');
            searchInput.className = 'dropdown-search';
            searchInput.innerHTML = '<input type="text" placeholder="Type to filter..." class="dropdown-search-input">';

            const optionsList = document.createElement('div');
            optionsList.className = 'dropdown-options-list';

            // Add original options
            Array.from(selectElement.options).forEach((option, index) => {
                if (index === 0) return; // Skip first option (placeholder)

                const optionDiv = document.createElement('div');
                optionDiv.className = 'dropdown-option';
                optionDiv.textContent = option.text;
                optionDiv.dataset.value = option.value;

                optionDiv.addEventListener('click', function() {
                    selectElement.value = this.dataset.value;
                    dropdownDisplay.textContent = this.textContent;
                    dropdownOptions.classList.remove('active');

                    // Trigger change event
                    selectElement.dispatchEvent(new Event('change'));

                    // Update UI
                    this.parentElement.querySelectorAll('.dropdown-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                });

                optionsList.appendChild(optionDiv);
            });

            dropdownOptions.appendChild(searchInput);
            dropdownOptions.appendChild(optionsList);
            wrapper.appendChild(dropdownDisplay);
            wrapper.appendChild(dropdownOptions);

            // Replace original select with enhanced version
            selectElement.style.display = 'none';
            selectElement.parentNode.insertBefore(wrapper, selectElement.nextSibling);

            // Toggle dropdown
            dropdownDisplay.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownOptions.classList.toggle('active');
                if (dropdownOptions.classList.contains('active')) {
                    dropdownOptions.querySelector('.dropdown-search-input').focus();
                }
            });

            // Filter options on search
            searchInput.querySelector('input').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const allOptions = optionsList.querySelectorAll('.dropdown-option');

                allOptions.forEach(option => {
                    const text = option.textContent.toLowerCase();
                    option.style.display = text.includes(searchTerm) ? 'block' : 'none';
                });
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!wrapper.contains(e.target)) {
                    dropdownOptions.classList.remove('active');
                }
            });
        }

        // ==================== API DATA LOADING ====================
        function loadDynamicData() {
            // Load silently – only show status on failure
            Promise.all([
                loadResource('getTitles', 'Title', 'TITLE_ID', 'TITLE_NAME', true),
                loadResource('getBrokers', 'BrokerFirm', 'BROKER_ID', 'BROKER_FULL_NAME', false, true),
                loadResource('getDistricts', 'ResAddressDistrict', 'DISTRICT_CODE', 'DISTRICT_NAME'),
                loadResource('getCountries', 'Country', 'COUNTRY_CODE', 'COUNTRY_NAME'),
                loadResource('getCountries', 'CountryOfResidency', 'COUNTRY_CODE', 'COUNTRY_NAME'),
                loadResource('getCountries', 'Nationality', 'COUNTRY_CODE', 'COUNTRY_NAME'),
                loadResource('getBanks', 'BankCode', 'BANK_CODE', 'BANK_NAME'),
                loadResource('getInvestAdvisors', 'InvestorId', 'INVESTOR_ID', 'INVESTOR_NAME', true)
            ]).catch(error => {
                showStatus('Failed to load form options: ' + error.message, 'error');
            });
        }

        function loadResource(action, selectId, valueField, displayField, optional, singleAsReadOnly) {
            return new Promise((resolve, reject) => {
                fetch(`resource.php?action=${action}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data && data.data.length > 0) {
                            populateDropdown(selectId, data.data, valueField, displayField, singleAsReadOnly);
                        }
                        if (optional) {
                            resolve();
                        } else if (!data.success || !data.data) {
                            reject(new Error(data.message || 'Failed to load ' + action));
                        } else {
                            resolve();
                        }
                    })
                    .catch(error => {
                        if (optional) resolve();
                        else reject(error);
                    });
            });
        }

        function populateDropdown(selectId, data, valueField, displayField, singleAsReadOnly) {
            const select = document.getElementById(selectId);
            if (!select) return;

            // When single item and singleAsReadOnly: show as read-only text, no dropdown
            if (singleAsReadOnly && data.length === 1) {
                const item = data[0];
                const value = item[valueField];
                const display = item[displayField] || item[valueField];
                const parent = select.parentNode;
                const span = document.createElement('span');
                span.className = 'broker-readonly';
                span.textContent = display;
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = select.name;
                hidden.id = select.id;
                hidden.value = value;
                parent.replaceChild(hidden, select);
                parent.insertBefore(span, hidden);
                const errMsg = parent.querySelector('.error-message');
                if (errMsg) errMsg.style.display = 'none';
                return;
            }

            // Clear existing options except first
            while (select.options.length > 1) {
                select.remove(1);
            }

            // Add data options
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item[valueField];
                option.textContent = item[displayField] || item[valueField];
                select.appendChild(option);
            });

            // Update enhanced dropdown if exists
            const enhancedWrapper = document.getElementById(selectId + '-wrapper');
            if (enhancedWrapper) {
                // Rebuild enhanced dropdown
                select.style.display = 'none';
                const newSelect = select.cloneNode(true);
                select.parentNode.replaceChild(newSelect, select);
                convertToEnhancedDropdown(newSelect);
            }
        }

        function loadBankBranches() {
            const bankCodeEl = document.getElementById('BankCode');
            const branchSelect = document.getElementById('BankBranch');
            if (!bankCodeEl || !branchSelect) return;

            const bankCode = bankCodeEl.value.trim();
            if (!bankCode) {
                while (branchSelect.options.length > 1) {
                    branchSelect.remove(1);
                }
                branchSelect.value = '';
                return;
            }

            // Inline loading: show "Loading..." in dropdown while fetching
            while (branchSelect.options.length > 1) branchSelect.remove(1);
            const loadingOpt = document.createElement('option');
            loadingOpt.value = '';
            loadingOpt.textContent = 'Loading...';
            loadingOpt.disabled = true;
            branchSelect.appendChild(loadingOpt);
            branchSelect.disabled = true;

            fetch(`resource.php?action=getBranches&BANK_CODE=${encodeURIComponent(bankCode)}`)
                .then(response => response.json())
                .then(data => {
                    let branches = data.data;
                    if (!Array.isArray(branches) && data.data && Array.isArray(data.data.data)) branches = data.data.data;
                    if (!Array.isArray(branches) && data.data && Array.isArray(data.data.Data)) branches = data.data.Data;

                    while (branchSelect.options.length > 1) branchSelect.remove(1);
                    branchSelect.disabled = false;

                    if (data.success && Array.isArray(branches) && branches.length > 0) {
                        branches.forEach(branch => {
                            const option = document.createElement('option');
                            const code = branch.BANK_BRANCH_CODE || branch.BankBranchCode || branch.code || '';
                            const name = branch.BANK_BRANCH_NAME || branch.BankBranchName || branch.name || '';
                            option.value = code || name;
                            option.textContent = name || code || 'Branch';
                            branchSelect.appendChild(option);
                        });
                    } else {
                        const opt = document.createElement('option');
                        opt.value = '';
                        opt.textContent = 'Select Branch';
                        branchSelect.appendChild(opt);
                        showStatus('Could not load branches. ' + (data.message || ''), 'error');
                    }
                })
                .catch(error => {
                    while (branchSelect.options.length > 1) branchSelect.remove(1);
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'Select Branch';
                    branchSelect.appendChild(opt);
                    branchSelect.disabled = false;
                    showStatus('Error loading branches: ' + error.message, 'error');
                });
        }

        // ==================== FORM SUBMISSION (two-step: form first, then images with AccountID) ====================
        function setSubmitLoading(loading) {
            const btn = document.getElementById('submit-btn');
            if (!btn) return;
            const text = btn.querySelector('.btn-text');
            const loader = btn.querySelector('.btn-loader');
            if (loading) {
                btn.disabled = true;
                btn.classList.add('btn-loading');
                const prevBtn = document.getElementById('prev-btn');
                if (prevBtn) prevBtn.disabled = true;
                if (text) text.style.display = 'none';
                if (loader) loader.style.display = 'inline';
            } else {
                isSubmitting = false;
                btn.disabled = false;
                btn.classList.remove('btn-loading');
                const prevBtn = document.getElementById('prev-btn');
                if (prevBtn) prevBtn.disabled = false;
                if (text) text.style.display = 'inline';
                if (loader) loader.style.display = 'none';
            }
        }

        function buildFormDataObject(form) {
            const obj = {};
            const radioDefaults = {
                'Gender': 'M', 'IsPEP': 'N', 'PEP_Q1': 'N', 'PEP_Q2': 'N', 'PEP_Q3': 'N', 'PEP_Q4': 'N',
                'LitigationStatus': 'N', 'BankAccountType': 'I', 'ResAddressStatus': 'Y', 'CorrAddressStatus': 'N',
                'OtherConnBusinessStatus': 'N', 'UsaPersonStatus': 'N', 'FactaDeclaration': 'N', 'DualCitizenship': 'N', 'IsLKPassport': 'N'
            };
            const elements = form.elements;
            for (let i = 0; i < elements.length; i++) {
                const el = elements[i];
                if (!el.name || el.disabled) continue;
                if (el.type === 'file') continue;
                if (el.type === 'checkbox' || el.type === 'radio') {
                    if (el.checked) obj[el.name] = el.value;
                } else if (el.tagName === 'SELECT') {
                    obj[el.name] = el.value;
                } else {
                    obj[el.name] = el.value;
                }
            }
            for (const name of Object.keys(radioDefaults)) {
                if (!(name in obj)) obj[name] = radioDefaults[name];
            }
            if (!obj.ClientType) obj.ClientType = 'FI';
            if (!obj.Residency) obj.Residency = 'R';
            if (!obj.Status) obj.Status = '1';
            if (!obj.EnterUser) obj.EnterUser = 'SYSTEM';
            if (!obj.ApiUser) obj.ApiUser = 'DIALOG';
            const userId = (obj.Email && obj.Email.trim()) || obj.UserID;
            obj.UserID = (userId && userId.trim()) ? userId : generateUserId();
            if (!obj.EnterDate || !obj.EnterDate.trim()) obj.EnterDate = new Date().toISOString().split('T')[0];
            obj.ApiRefNo = ('REF' + Date.now()).slice(0, 15);
            return obj;
        }

        let isSubmitting = false;
        function submitForm() {
            if (isSubmitting) return;
            if (!validateCurrentStep()) return;
            isSubmitting = true;

            const form = document.getElementById('cdsAccountForm');
            const formDataObj = buildFormDataObject(form);

            setSubmitLoading(true);
            showStatus('Creating account...', 'info');

            // Step 1: Submit form data only (no files) – get AccountID
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ formData: formDataObj, step: 'submit' })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    showStatus('Error: ' + data.message, 'error');
                    showApiErrorsBelowFields(data.message);
                    setSubmitLoading(false);
                    return Promise.resolve();
                }
                const accountId = data.accountId;
                showStatus('Account created. Uploading documents...', 'info');

                // Step 2: Upload images with AccountID (after account exists)
                const uploadFormData = new FormData();
                uploadFormData.append('step', 'upload');
                uploadFormData.append('accountId', accountId);
                uploadFormData.append('UserID', formDataObj.UserID);
                uploadFormData.append('Email', formDataObj.Email || '');
                const fileInputs = ['selfie_upload', 'nic_front_upload', 'nic_back_upload', 'passport_upload'];
                let hasFiles = false;
                for (const name of fileInputs) {
                    const input = form.querySelector(`[name="${name}"]`);
                    if (input && input.files && input.files[0]) {
                        uploadFormData.append(name, input.files[0]);
                        hasFiles = true;
                    }
                }

                if (!hasFiles) {
                    showStatus(
                        `Application submitted successfully!<br>Account ID: ${accountId}<br>Source Funds: ${data.sourceFundsSaved ? 'Saved' : 'Failed'}`,
                        'success'
                    );
                    setSubmitLoading(false);
                    setTimeout(() => { resetForm(); goToStep(1); }, 5000);
                    return Promise.resolve();
                }

                return fetch('api.php', { method: 'POST', body: uploadFormData })
                    .then(r => r.json())
                    .then(uploadData => {
                        showStatus(
                            `Application submitted successfully!<br>
                            Account ID: ${accountId}<br>
                            Source Funds: ${data.sourceFundsSaved ? 'Saved' : 'Failed'}<br>
                            Images: ${uploadData.imagesUploaded ? 'Uploaded' : 'Failed'}`,
                            'success'
                        );
                        setSubmitLoading(false);
                        setTimeout(() => { resetForm(); goToStep(1); }, 5000);
                    });
            })
            .catch(error => {
                showStatus('Network error: ' + error.message, 'error');
                setSubmitLoading(false);
            });
        }

        // ==================== HELPER FUNCTIONS ====================
        function generateUserId() {
            return 'USER_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        }

        function setupDateDefaults() {
            // Set current date for date fields
            const today = new Date().toISOString().split('T')[0];
            const enterDateField = document.getElementById('EnterDate');
            if (enterDateField) enterDateField.value = today;

            // Set default date of birth (25 years ago)
            const defaultDob = new Date();
            defaultDob.setFullYear(defaultDob.getFullYear() - 25);
            const dobField = document.getElementById('DateOfBirthday');
            if (dobField) dobField.valueAsDate = defaultDob;
        }

        function updateProgressBar() {
            const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
            const progressBar = document.getElementById('form-progress');
            if (progressBar) {
                progressBar.style.width = progress + '%';
            }
        }

        // ==================== VALIDATION FUNCTIONS ====================
        function setupFormValidation() {
            // Add real-time validation
            document.addEventListener('blur', function(event) {
                if (event.target.matches('input, select, textarea')) {
                    validateField(event.target);
                }
            }, true);

            // Clear errors on input
            document.addEventListener('input', function(event) {
                if (event.target.matches('input, select, textarea')) {
                    clearFieldError(event.target);
                }
            });

            // Image file validation (2MB limit, JPG/PNG only) + preview
            const form = document.getElementById('cdsAccountForm');
            if (form) {
                form.addEventListener('change', function(e) {
                    if (e.target && e.target.type === 'file' && ['selfie_upload', 'nic_front_upload', 'nic_back_upload', 'passport_upload'].includes(e.target.id)) {
                        previewImage(e.target, 'preview-' + e.target.id);
                    }
                });
            }
        }

        function validateField(field) {
            let isValid = true;
            let errorMessage = '';

            // Check if field is required and empty
            if (field.hasAttribute('required') && !field.value.trim()) {
                errorMessage = 'This field is required';
                isValid = false;
            }

            // Validate email format
            if (field.type === 'email' && field.value && !isValidEmail(field.value)) {
                errorMessage = 'Please enter a valid email address';
                isValid = false;
            }

            // Validate phone numbers
            if ((field.id === 'MobileNo' || field.id === 'TelphoneNo') && field.value && !isValidPhone(field.value)) {
                errorMessage = 'Please enter a valid phone number';
                isValid = false;
            }

            // Validate NIC format
            if (field.id === 'NicNo' && field.value && !isValidNIC(field.value)) {
                errorMessage = 'Please enter a valid NIC number';
                isValid = false;
            }

            // Validate date (not in future) - only for Date of Birth; Passport Expiry should be in future
            if (field.type === 'date' && field.id === 'DateOfBirthday' && field.value) {
                const inputDate = new Date(field.value);
                const today = new Date();
                if (inputDate > today) {
                    errorMessage = 'Date of birth cannot be in the future';
                    isValid = false;
                }
            }

            // Validate maxlength
            if (field.hasAttribute('maxlength') && field.value.length > parseInt(field.getAttribute('maxlength'))) {
                errorMessage = `Maximum ${field.getAttribute('maxlength')} characters allowed`;
                isValid = false;
            }

            if (!isValid) {
                showFieldError(field, errorMessage);
            } else {
                clearFieldError(field);
            }

            return isValid;
        }

        function showFieldError(field, message) {
            const formGroup = field.closest('.form-group');
            if (formGroup) {
                formGroup.classList.add('field-error');
                let errorElement = formGroup.querySelector('.error-message');
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'error-message';
                    formGroup.appendChild(errorElement);
                }
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
        }

        function clearFieldError(field) {
            const formGroup = field.closest('.form-group');
            if (formGroup) {
                formGroup.classList.remove('field-error');
                const errorElement = formGroup.querySelector('.error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        }

        function clearAllErrors() {
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach(group => {
                group.classList.remove('field-error');
                const errorElement = group.querySelector('.error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            });
        }

        function scrollToFirstError(stepElement) {
            if (!stepElement) return;
            const firstError = stepElement.querySelector('.form-group.field-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const input = firstError.querySelector('input, select, textarea');
                if (input) {
                    input.focus();
                }
            }
        }

        /** Parse API "Required fields missing..." error and show below each field */
        function showApiErrorsBelowFields(message) {
            const match = message && message.match(/Required fields missing \(doc Null\? Y\):\s*(.+)/i);
            if (!match) return;

            clearAllErrors();
            const fieldNames = match[1].split(',').map(s => s.trim()).filter(Boolean);
            let firstErrorGroup = null;

            fieldNames.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    showFieldError(field, 'This field is required');
                    if (!firstErrorGroup) {
                        firstErrorGroup = field.closest('.form-group');
                    }
                }
            });

            if (firstErrorGroup) {
                const stepEl = firstErrorGroup.closest('.form-step');
                if (stepEl) {
                    const stepNum = parseInt(stepEl.id.replace('step-', ''), 10);
                    if (!isNaN(stepNum)) {
                        currentStep = stepNum;
                        showStep(stepNum);
                    }
                }
                firstErrorGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const input = firstErrorGroup.querySelector('input, select, textarea');
                if (input) input.focus();
            }
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function isValidMobile(mobile) {
            const mobileRegex = /^(\+94|0)[1-9][0-9]{8}$/;
            return mobileRegex.test(mobile.replace(/\s/g, ''));
        }

        function isValidPhone(phone) {
            const phoneRegex = /^[\d\s\+\-\(\)]{10,16}$/;
            return phoneRegex.test(phone);
        }

        function isValidNIC(nic) {
            const oldNicRegex = /^[0-9]{9}[VX]$/i;
            const newNicRegex = /^[0-9]{12}$/;
            return oldNicRegex.test(nic) || newNicRegex.test(nic);
        }

        function isValidAge(dateString, minAge) {
            const today = new Date();
            const birthDate = new Date(dateString);
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            return age >= minAge;
        }

        // ==================== CONDITIONAL FIELD FUNCTIONS ====================
        function setupConditionalFields() {
            // Initial toggle based on current values
            toggleIdentificationFields();
            toggleEmploymentFields();
            togglePEPQuestions();
            toggleLitigationDetails();

            // Add event listeners
            document.getElementById('IdentificationProof')?.addEventListener('change', toggleIdentificationFields);
            document.getElementById('EmployeStatus')?.addEventListener('change', toggleEmploymentFields);

            // PEP radio buttons
            document.querySelectorAll('input[name="IsPEP"]').forEach(radio => {
                radio.addEventListener('change', togglePEPQuestions);
            });

            // Litigation radio buttons
            document.querySelectorAll('input[name="LitigationStatus"]').forEach(radio => {
                radio.addEventListener('change', toggleLitigationDetails);
            });

            // Existing CDS account radio buttons
            document.querySelectorAll('input[name="ExitCDSAccount"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const cdsAccountField = document.getElementById('cds-account-field');
                    if (cdsAccountField) {
                        cdsAccountField.style.display = this.value === 'Y' ? 'block' : 'none';
                    }
                });
            });
        }

        function toggleIdentificationFields() {
            const idType = document.getElementById('IdentificationProof')?.value;
            const nicField = document.getElementById('nic-field');
            const passportField = document.getElementById('passport-field');
            const passportExpField = document.getElementById('passport-exp-field');

            if (idType === 'NIC') {
                if (nicField) nicField.style.display = 'block';
                if (passportField) passportField.style.display = 'none';
                if (passportExpField) passportExpField.style.display = 'none';
            } else if (idType === 'Passport') {
                if (nicField) nicField.style.display = 'none';
                if (passportField) passportField.style.display = 'block';
                if (passportExpField) passportExpField.style.display = 'block';
            } else {
                if (nicField) nicField.style.display = 'none';
                if (passportField) passportField.style.display = 'none';
                if (passportExpField) passportExpField.style.display = 'none';
            }
        }

        function toggleEmploymentFields() {
            const employmentStatus = document.getElementById('EmployeStatus')?.value;
            const employmentDetails = document.getElementById('employment-details');

            if (employmentDetails) {
                if (['Y', 'S'].includes(employmentStatus)) {
                    employmentDetails.classList.add('active');
                } else {
                    employmentDetails.classList.remove('active');
                }
            }
        }

        function togglePEPQuestions() {
            const isPEP = document.querySelector('input[name="IsPEP"]:checked');
            const pepQuestions = document.getElementById('pep-questions');

            if (pepQuestions) {
                if (isPEP && isPEP.value === 'Y') {
                    pepQuestions.classList.add('active');
                } else {
                    pepQuestions.classList.remove('active');
                }
            }
        }

        function toggleLitigationDetails() {
            const litigationStatus = document.querySelector('input[name="LitigationStatus"]:checked');
            const litigationDetails = document.getElementById('litigation-details');

            if (litigationDetails) {
                if (litigationStatus && litigationStatus.value === 'Y') {
                    litigationDetails.classList.add('active');
                } else {
                    litigationDetails.classList.remove('active');
                }
            }
        }

        // ==================== IMAGE FUNCTIONS ====================
        function previewImage(input, previewId) {
            const file = input.files[0];
            if (!file) return;

            // Validate file size
            if (file.size > 2 * 1024 * 1024) {
                showFieldError(input, 'File size must be less than 2MB');
                input.value = '';
                return;
            }

            // Validate file type
            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                showFieldError(input, 'Only JPG and PNG files are allowed');
                input.value = '';
                return;
            }

            clearFieldError(input);

            const reader = new FileReader();
            reader.onload = function(e) {
                let previewContainer = document.getElementById('image-previews');
                if (!previewContainer) return;

                let previewDiv = document.getElementById(previewId);

                if (!previewDiv) {
                    previewDiv = document.createElement('div');
                    previewDiv.id = previewId;
                    previewDiv.className = 'preview-item';
                    previewContainer.appendChild(previewDiv);
                }

                const imageTypeMap = {
                    'selfie_upload': 'Selfie',
                    'nic_front_upload': 'NIC Front',
                    'nic_back_upload': 'NIC Back',
                    'passport_upload': 'Passport'
                };

                previewDiv.innerHTML = `
            <img src="${e.target.result}" class="preview-img" alt="${imageTypeMap[input.id] || 'Document'}">
            <p>${imageTypeMap[input.id] || 'Document'}</p>
            <button type="button" onclick="removeImage('${input.id}', '${previewId}')" style="background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; margin-top: 5px;">
                <i class="fas fa-times"></i> Remove
            </button>
        `;
            };

            reader.readAsDataURL(file);
        }

        function removeImage(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);

            if (input) input.value = '';
            if (preview) preview.remove();
        }

        // ==================== STATUS FUNCTION ====================
        function showStatus(message, type) {
            const statusElement = document.getElementById('status-message');
            if (!statusElement) return;

            statusElement.innerHTML = message;
            statusElement.className = 'status-message status-' + type;
            statusElement.style.display = 'block';

            // Auto-hide success messages after 10 seconds
            if (type === 'success') {
                setTimeout(() => {
                    statusElement.style.display = 'none';
                }, 10000);
            }
        }

        // ==================== FORM RESET ====================
        function resetForm() {
            const form = document.getElementById('cdsAccountForm');
            if (form) form.reset();

            clearAllErrors();

            const imagePreviews = document.getElementById('image-previews');
            if (imagePreviews) imagePreviews.innerHTML = '';

            setupConditionalFields();
            setupDateDefaults();
        }

        // ==================== EVENT LISTENERS ====================
        // Bank branches: use delegation so it works after form steps are rebuilt
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('cdsAccountForm');
            if (form) {
                form.addEventListener('change', function(e) {
                    if (e.target && e.target.id === 'BankCode') {
                        loadBankBranches();
                    }
                });
            }
        });
    </script>








</body>

</html>