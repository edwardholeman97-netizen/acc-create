<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDS Account Opening</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .form-container {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 40px;
            padding: 25px;
            background-color: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .section-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: #3498db;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px 20px;
        }

        .form-group {
            flex: 1 0 300px;
            padding: 0 10px;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
            font-size: 14px;
        }

        .required::after {
            content: " *";
            color: #e74c3c;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .radio-group,
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 5px;
        }

        .radio-option,
        .checkbox-option {
            display: flex;
            align-items: center;
        }

        .radio-option input,
        .checkbox-option input {
            width: auto;
            margin-right: 8px;
        }

        .form-note {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 5px;
            font-style: italic;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit {
            background-color: #2ecc71;
            color: white;
        }

        .btn-submit:hover {
            background-color: #27ae60;
        }

        .btn-reset {
            background-color: #e74c3c;
            color: white;
        }

        .btn-reset:hover {
            background-color: #c0392b;
        }

        .conditional-field {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background-color: #f0f7ff;
            border-radius: 6px;
            border-left: 3px solid #3498db;
        }

        .conditional-field.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }

        .field-error input,
        .field-error select,
        .field-error textarea {
            border-color: #e74c3c;
        }

        .field-error .error-message {
            display: block;
        }

        .progress-bar {
            height: 5px;
            background: #e0e0e0;
            margin: 20px 0;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: #2ecc71;
            width: 0%;
            transition: width 0.5s ease;
        }

        .status-message {
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
            display: none;
        }

        .status-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .upload-section {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }

        .upload-btn {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            margin-top: 10px;
        }

        .upload-btn:hover {
            background-color: #2980b9;
        }

        .preview-container {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .preview-item {
            width: 150px;
            text-align: center;
        }

        .preview-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        /* Step Navigation */
        .step-navigation {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            padding: 0 20px;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 10px 0;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #ddd;
            z-index: 1;
        }

        .step.active:not(:last-child)::after {
            background: #2ecc71;
        }

        .step-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            background: #ddd;
            color: #666;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }

        .step.active .step-number {
            background: #2ecc71;
            color: white;
        }

        .step-title {
            display: block;
            margin-top: 8px;
            font-size: 14px;
            color: #666;
        }

        .step.active .step-title {
            color: #2ecc71;
            font-weight: 600;
        }

        /* Form Steps */
        .form-steps-container {
            position: relative;
        }

        .form-step {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .form-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Step Buttons */
        .step-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }

        .btn-prev {
            background-color: #95a5a6;
            color: white;
        }

        .btn-prev:hover {
            background-color: #7f8c8d;
        }

        .btn-next {
            background-color: #3498db;
            color: white;
        }

        .btn-next:hover {
            background-color: #2980b9;
        }

        /* Enhanced Dropdown */
        .enhanced-dropdown {
            position: relative;
        }

        .searchable-dropdown {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .searchable-dropdown::after {
            content: '▼';
            font-size: 12px;
            color: #666;
        }

        .dropdown-options {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .dropdown-options.active {
            display: block;
        }

        .dropdown-search {
            padding: 10px;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            background: white;
            z-index: 2;
        }

        .dropdown-search input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .dropdown-options-list {
            max-height: 250px;
            overflow-y: auto;
        }

        .dropdown-option {
            padding: 10px 15px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .dropdown-option:hover {
            background: #f5f5f5;
        }

        .dropdown-option.selected {
            background: #3498db;
            color: white;
        }

        @media (max-width: 768px) {
            .form-group {
                flex: 1 0 100%;
            }

            .form-section {
                padding: 15px;
            }

            .form-container {
                padding: 15px;
            }
        }
    </style>
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
    
    <div class="status-message" id="status-message"></div>

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
        <form id="cdsAccountForm">
            
            <!-- Form Steps Container -->
            <div class="form-steps-container">
                <!-- Step 1: Personal Information -->
                <div class="form-step active" id="step-1">
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-user"></i> Personal Information</h2>
                         
<!-- <div class="resume-section">
    <label for="resume_account_id">Have an existing Account ID?</label>
    <input type="text" id="resume_account_id" placeholder="Enter Account ID">
    <button type="button" onclick="resumeSession()">Resume Application</button>
</div> -->
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="Title" class="required">Title</label>
                                <select id="Title" name="Title" required>
                                    <option value="">Select Title</option>
                                    <option value="MR.">Mr</option>
                                    <option value="MRS.">Mrs</option>
                                    <option value="MISS.">Miss</option>
                                    <option value="DR.">Dr</option>
                                    <option value="PROF.">Prof</option>
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
                                    <option value="NIC">NIC</option>
                                    <option value="Passport">Passport</option>
                                </select>
                                <div class="error-message">Please select identification proof type</div>
                            </div>
                            
                            <div class="form-group" id="nic-field">
                                <label for="NicNo" class="required">NIC No</label>
                                <input type="text" id="NicNo" name="NicNo" maxlength="12" pattern="[0-9]{9,12}[VX]?" title="Enter valid NIC number (e.g., 123456789V)">
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
                                <div class="form-note">Data from: /api/OtherServices/GetBroker</div>
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
                                    <option value="Y">Employed</option>
                                    <option value="N">Unemployed</option>
                                    <option value="S">Self-Employed</option>
                                    <option value="T">Student</option>
                                    <option value="R">Retired</option>
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
                                <div class="form-note">Data from: /api/OtherServices/GetBank</div>
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
                                <div class="form-note">Data from: /api/OtherServices/GetBankBranch</div>
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
                                    <option value="1">Less than LKR 100,000</option>
                                    <option value="2">LKR 100,000 - 500,000</option>
                                    <option value="3">LKR 500,000 - 1,000,000</option>
                                    <option value="4">LKR 1,000,000 - 5,000,000</option>
                                    <option value="5">More than LKR 5,000,000</option>
                                </select>
                                <div class="form-note">Ref. excel sheet</div>
                                <div class="error-message">Please select expected investment value</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="SourseOfFund">Source of Funds</label>
                                <select id="SourseOfFund" name="SourseOfFund">
                                    <option value="">Select Source</option>
                                    <option value="1">Salary/Profit Income</option>
                                    <option value="2">Investment Proceeds/Savings</option>
                                    <option value="3">Sales and Business Turnover</option>
                                    <option value="4">Contract Proceeds</option>
                                    <option value="5">Sales of Property/Assets</option>
                                    <option value="6">Gifts</option>
                                    <option value="7">Donations/Charities</option>
                                    <option value="8">Commission Income</option>
                                    <option value="9">Family Remittance</option>
                                    <option value="10">Export proceeds</option>
                                    <option value="11">Membership contribution</option>
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
                                    <label for="PEP_Q1">Are you a Politically Exposed Person?</label>
                                    <div class="radio-group">
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q1_y" name="PEP_Q1" value="Y">
                                            <label for="pep_q1_y">Yes</label>
                                        </div>
                                        <div class="radio-option">
                                            <input type="radio" id="pep_q1_n" name="PEP_Q1" value="N">
                                            <label for="pep_q1_n">No</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="PEP_Q1_Details">If Yes, provide details</label>
                                    <input type="text" id="PEP_Q1_Details" name="PEP_Q1_Details" maxlength="100">
                                </div>
                            </div>
                            <!-- Note: Additional PEP questions (Q2, Q3, Q4) should be added here if needed -->
                        </div>
                        
                        <!-- Litigation Details (Conditional) -->
                        <div class="conditional-field" id="litigation-details">
                            <div class="form-group">
                                <label for="LitigationDetails">Litigation Details</label>
                                <textarea id="LitigationDetails" name="LitigationDetails" maxlength="100" rows="3"></textarea>
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
                                    <div class="form-note">IMAGE_TYPE: 1</div>
                                    <div class="error-message">Please upload selfie photo</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nic_front_upload" class="required">NIC Front</label>
                                    <input type="file" id="nic_front_upload" name="nic_front_upload" accept="image/jpeg,image/png">
                                    <div class="form-note">IMAGE_TYPE: 2</div>
                                    <div class="error-message">Please upload NIC front photo</div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nic_back_upload" class="required">NIC Back</label>
                                    <input type="file" id="nic_back_upload" name="nic_back_upload" accept="image/jpeg,image/png">
                                    <div class="form-note">IMAGE_TYPE: 3</div>
                                    <div class="error-message">Please upload NIC back photo</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="passport_upload">Passport (if applicable)</label>
                                    <input type="file" id="passport_upload" name="passport_upload" accept="image/jpeg,image/png">
                                    <div class="form-note">IMAGE_TYPE: 4</div>
                                </div>
                            </div>
                            
                            <div class="preview-container" id="image-previews"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Information (Hidden) -->
            <input type="hidden" id="ClientType" name="ClientType" value="FI">
            <input type="hidden" id="ApiUser" name="ApiUser" value="DIALOG">
            <input type="hidden" id="Status" name="Status" value="1">
            <input type="hidden" id="EnterUser" name="EnterUser" value="SYSTEM">
            <input type="hidden" id="UserID" name="UserID">
            <input type="hidden" id="EnterDate" name="EnterDate">
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
                    <i class="fas fa-paper-plane"></i> Submit Application
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
            <i class="fas fa-paper-plane"></i> Submit Application
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
            requiredFields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });

            // Special validation for step 2 (Identification)
            if (currentStep === 2) {
                const idType = document.getElementById('IdentificationProof').value;
                if (idType === 'NIC') {
                    const nicField = document.getElementById('NicNo');
                    if (nicField.value && !isValidNIC(nicField.value)) {
                        showFieldError(nicField, 'Please enter valid NIC number');
                        isValid = false;
                    }
                } else if (idType === 'Passport') {
                    const passportField = document.getElementById('PassportNo');
                    if (!passportField.value) {
                        showFieldError(passportField, 'Passport number is required');
                        isValid = false;
                    }
                }
            }

            if (!isValid) {
                showStatus('Please fill all required fields correctly', 'error');
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
            showStatus('Loading data...', 'info');

            // Load all data in parallel
            Promise.all([
                loadResource('getBrokers', 'BrokerFirm', 'BROKER_ID', 'BROKER_FULL_NAME'),
                loadResource('getDistricts', 'ResAddressDistrict', 'DISTRICT_CODE', 'DISTRICT_NAME'),
                loadResource('getCountries', 'Country', 'COUNTRY_CODE', 'COUNTRY_NAME'),
                loadResource('getCountries', 'CountryOfResidency', 'COUNTRY_CODE', 'COUNTRY_NAME'),
                loadResource('getCountries', 'Nationality', 'COUNTRY_CODE', 'COUNTRY_NAME'),
                loadResource('getBanks', 'BankCode', 'BANK_CODE', 'BANK_NAME')
            ]).then(() => {
                showStatus('Data loaded successfully', 'success');
                setTimeout(() => {
                    document.getElementById('status-message').style.display = 'none';
                }, 2000);
            }).catch(error => {
                showStatus('Failed to load data: ' + error.message, 'error');
            });
        }

        function loadResource(action, selectId, valueField, displayField) {
            return new Promise((resolve, reject) => {
                fetch(`resource.php?action=${action}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            populateDropdown(selectId, data.data, valueField, displayField);
                            resolve();
                        } else {
                            reject(new Error(data.message || 'Failed to load ' + action));
                        }
                    })
                    .catch(error => reject(error));
            });
        }

        function populateDropdown(selectId, data, valueField, displayField) {
            const select = document.getElementById(selectId);
            if (!select) return;

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
            const bankCode = document.getElementById('BankCode').value;
            const branchSelect = document.getElementById('BankBranch');

            if (!bankCode) {
                // Clear branches
                while (branchSelect.options.length > 1) {
                    branchSelect.remove(1);
                }
                return;
            }

            // Show loading
            showStatus('Loading bank branches...', 'info');

            fetch(`resource.php?action=getBranches&BANK_CODE=${bankCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        // Clear existing branches
                        while (branchSelect.options.length > 1) {
                            branchSelect.remove(1);
                        }

                        // Add new branches
                        data.data.forEach(branch => {
                            const option = document.createElement('option');
                            option.value = branch.BANK_BRANCH_CODE || branch.BANK_BRANCH_NAME;
                            option.textContent = branch.BANK_BRANCH_NAME || branch.BANK_BRANCH_NAME;
                            branchSelect.appendChild(option);
                        });

                        showStatus('Branches loaded', 'success');
                        setTimeout(() => {
                            document.getElementById('status-message').style.display = 'none';
                        }, 1000);
                    } else {
                        showStatus('Failed to load branches: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    showStatus('Error loading branches: ' + error.message, 'error');
                });
        }

        // ==================== FORM SUBMISSION ====================
        function submitForm() {
            if (!validateCurrentStep()) {
                showStatus('Please fix errors before submitting', 'error');
                return;
            }

            // Prepare form data
            const form = document.getElementById('cdsAccountForm');
            const formData = new FormData();

            // Add all form fields
            const formElements = form.elements;
            for (let i = 0; i < formElements.length; i++) {
                const element = formElements[i];
                if (element.name && !element.disabled) {
                    if (element.type === 'file') {
                        // Files will be added separately
                        if (element.files && element.files[0]) {
                            formData.append(element.name, element.files[0]);
                        }
                        continue;
                    } else if (element.type === 'checkbox' || element.type === 'radio') {
                        if (element.checked) {
                            formData.append(element.name, element.value);
                        }
                    } else if (element.tagName === 'SELECT') {
                        formData.append(element.name, element.value);
                    } else {
                        formData.append(element.name, element.value);
                    }
                }
            }

            // Add hidden fields
            formData.append('UserID', generateUserId());
            formData.append('EnterDate', new Date().toISOString().split('T')[0]);
            formData.append('ApiRefNo', 'REF-' + Date.now());
            formData.append('ResAddressStatus', 'Y');

            // Show loading
            showStatus('Submitting application...', 'info');
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('prev-btn').disabled = true;

            // Submit to api.php
            fetch('api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showStatus(
                            `Application submitted successfully!<br>
                Account ID: ${data.accountId}<br>
                Source Funds: ${data.sourceFundsSaved ? 'Saved' : 'Failed'}<br>
                Images: ${data.imagesUploaded ? 'Uploaded' : 'Failed'}`,
                            'success'
                        );

                        // Reset form after delay
                        setTimeout(() => {
                            resetForm();
                            goToStep(1);
                        }, 5000);
                    } else {
                        showStatus('Error: ' + data.message, 'error');
                    }
                    document.getElementById('submit-btn').disabled = false;
                    document.getElementById('prev-btn').disabled = false;
                })
                .catch(error => {
                    showStatus('Network error: ' + error.message, 'error');
                    document.getElementById('submit-btn').disabled = false;
                    document.getElementById('prev-btn').disabled = false;
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

            // Validate date (not in future)
            if (field.type === 'date' && field.value) {
                const inputDate = new Date(field.value);
                const today = new Date();
                if (inputDate > today) {
                    errorMessage = 'Date cannot be in the future';
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

            showStatus('Form has been reset', 'info');
            setTimeout(() => {
                const statusMsg = document.getElementById('status-message');
                if (statusMsg) statusMsg.style.display = 'none';
            }, 3000);
        }

        // ==================== EVENT LISTENERS ====================
        // Attach bank code change listener
        document.addEventListener('DOMContentLoaded', function() {
            const bankCodeSelect = document.getElementById('BankCode');
            if (bankCodeSelect) {
                bankCodeSelect.addEventListener('change', loadBankBranches);
            }
        });


        function resumeSession() {
            const accountId = document.getElementById('resume_account_id').value.trim();
            if (!accountId) {
                showStatus('Please enter an Account ID', 'error');
                return;
            }
            // Verify with server (optional)
            fetch(`resource.php?action=verifyAccount&accountId=${accountId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentAccountId = accountId;
                        localStorage.setItem('cse_account_id', accountId);
                        addHiddenAccountField();
                        // Jump to step 2 (or whichever step is appropriate)
                        currentStep = 2;
                        showStep(2);
                        showStatus('Session resumed for Account ID: ' + accountId, 'success');
                    } else {
                        showStatus('Invalid Account ID', 'error');
                    }
                })
                .catch(() => showStatus('Error verifying Account ID', 'error'));
        }


    </script>








</body>

</html>