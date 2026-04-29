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
                                <input type="text" id="Initials" name="Initials" maxlength="15" required placeholder="M. P. S. A.">
                                <div class="error-message">Please enter initials (max 15 characters)</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="Surname" class="required">Surname</label>
                                <input type="text" id="Surname" name="Surname" maxlength="50" required placeholder="Perera">
                                <div class="error-message">Please enter surname</div>
                            </div>

                            <div class="form-group">
                                <label for="NameDenoInitials" class="required">Full Name (Denoted by Initials)</label>
                                <input type="text" id="NameDenoInitials" name="NameDenoInitials" maxlength="160" required  placeholder="M. P. S. A. Perera">
                                <div class="error-message">Please enter full name</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="MobileNo" class="required">Mobile Number</label>
                                <input type="tel" id="MobileNo" name="MobileNo" maxlength="16" required pattern="[0-9+]{10,16}" placeholder="077 123 4567">
                                <div class="error-message">Please enter a valid mobile number (10-16 digits)</div>
                            </div>

                            <div class="form-group">
                                <label for="TelphoneNo">Telephone Number</label>
                                <input type="tel" id="TelphoneNo" name="TelphoneNo" maxlength="16" placeholder="011 223 4455">
                            </div>

                            <div class="form-group">
                                <label for="Email" class="required">E-Mail Address</label>
                                <input type="email" id="Email" name="Email" maxlength="100" required placeholder="amalperera@gmail.com">
                                <div class="error-message">Please enter a valid email address</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="DateOfBirthday" class="required">Date of Birth</label>
                                <input type="date" id="DateOfBirthday" name="DateOfBirthday" required placeholder="01/15/1985">
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
                                <input type="text" required id="NicNo" name="NicNo" maxlength="12" pattern="[0-9]{9,12}[VX]?" title="Enter valid NIC number (e.g., 123456789V)" placeholder="123456789V">
                                <div class="error-message">Please enter valid NIC number</div>
                            </div>

                            <div class="form-group" id="passport-field" style="display: none;">
                                <label for="PassportNo" class="required">Passport No</label>
                                <input type="text" id="PassportNo" name="PassportNo" maxlength="50" placeholder="L1234567">
                                <div class="error-message">Please enter passport number</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group" id="passport-exp-field" style="display: none;">
                                <label for="PassportExpDate" class="required">Passport Expiry Date</label>
                                <input type="date" id="PassportExpDate" name="PassportExpDate" placeholder="12/31/2030">
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
                                <input type="text" id="CDSAccountNo" name="CDSAccountNo" maxlength="20" placeholder="123456789012">
                            </div>

                            <div class="form-group">
                                <label for="TinNo">TIN Number</label>
                                <input type="text" id="TinNo" name="TinNo" maxlength="20" placeholder="200012345678">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="InvestorId">Investor / Advisor</label>
                                <select id="InvestorId" name="InvestorId">
                                    <option value="">None</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="InvestmentOb">Investment Objectives</label>
                                <textarea id="InvestmentOb" name="InvestmentOb" maxlength="100" rows="3" placeholder="Long-term growth (10+ years)"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="InvestmentStrategy">Investment Strategy</label>
                                <textarea id="InvestmentStrategy" name="InvestmentStrategy" maxlength="200" rows="3" placeholder="Balanced - 50% Stocks, 50% Bonds"></textarea>
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
                                <input type="text" id="ResAddressLine01" name="ResAddressLine01" maxlength="30" required placeholder="456 Galle Road">
                                <div class="error-message">Please enter address line 1</div>
                            </div>

                            <div class="form-group">
                                <label for="ResAddressLine02">Address Line 2</label>
                                <input type="text" id="ResAddressLine02" name="ResAddressLine02" maxlength="30" placeholder="Colombo 3">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="ResAddressLine03">Address Line 3</label>
                                <input type="text" id="ResAddressLine03" name="ResAddressLine03" maxlength="15" placeholder="Near Liberty Plaza">
                            </div>

                            <div class="form-group">
                                <label for="ResAddressTown" class="required">Town</label>
                                <input type="text" id="ResAddressTown" name="ResAddressTown" maxlength="15" required placeholder="Colombo">
                                <div class="error-message">Please enter Town</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="ResAddressDistrict" class="required">District</label>
                                <select id="ResAddressDistrict" name="ResAddressDistrict" required>
                                    <option value="">Select District</option>
                                </select>
                                <div class="error-message">Please select district</div>
                            </div>

                            <div class="form-group">
                                <label for="Country" class="required">Country</label>
                                <select id="Country" name="Country" required>
                                    <option value="">Select Country</option>
                                </select>
                                <div class="error-message">Please select country</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="CountryOfResidency" class="required">Country of Residency</label>
                                <select id="CountryOfResidency" name="CountryOfResidency" required>
                                    <option value="">Select Country</option>
                                </select>
                                <div class="error-message">Please select country of residency</div>
                            </div>

                            <div class="form-group">
                                <label for="Nationality" class="required">Nationality</label>
                                <select id="Nationality" name="Nationality" required>
                                    <option value="">Select Nationality</option>
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
                                <input type="text" id="CorrAddressLine01" name="CorrAddressLine01" maxlength="30" placeholder="456 Galle Road">
                            </div>
                            <div class="form-group">
                                <label for="CorrAddressLine02">Corr Address Line 2</label>
                                <input type="text" id="CorrAddressLine02" name="CorrAddressLine02" maxlength="30" placeholder="Colombo 3">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="CorrAddressLine03">Corr Address Line 3</label>
                                <input type="text" id="CorrAddressLine03" name="CorrAddressLine03" maxlength="15" placeholder="Opposite Cinnamon Grand Hotel">
                            </div>
                            <div class="form-group">
                                <label for="CorrAddressTown">Corr Address Town</label>
                                <input type="text" id="CorrAddressTown" name="CorrAddressTown" maxlength="15" placeholder="Colombo">
                            </div>
                            <div class="form-group">
                                <label for="CorrAddressDistrict">Corr Address District</label>
                                <input type="text" id="CorrAddressDistrict" name="CorrAddressDistrict" maxlength="10" placeholder="Colombo">
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
                                <input type="text" id="Occupation" name="Occupation" maxlength="50" placeholder="Marketing Manager">
                            </div>
                        </div>

                        <div class="conditional-field" id="employment-details">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="NameOfEmployer">Name of Employer</label>
                                    <input type="text" id="NameOfEmployer" name="NameOfEmployer" maxlength="100" placeholder="XYZ Corporation Pvt Ltd">
                                </div>

                                <div class="form-group">
                                    <label for="AddressOfEmployer">Address of Employer</label>
                                    <input type="text" id="AddressOfEmployer" name="AddressOfEmployer" maxlength="150" placeholder="123 Marketing Plaza, 2nd Floor, Colombo 5">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="OfficePhoneNo">Office Phone Number</label>
                                    <input type="tel" id="OfficePhoneNo" name="OfficePhoneNo" maxlength="16" placeholder="011 456 7890">
                                </div>

                                <div class="form-group">
                                    <label for="OfficeEmail">Office Email</label>
                                    <input type="email" id="OfficeEmail" name="OfficeEmail" maxlength="100" placeholder="hr@xyzcorporation.com">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="EmployeeComment">Employee Comment</label>
                                    <textarea id="EmployeeComment" name="EmployeeComment" maxlength="500" rows="3" placeholder="Employed at XYZ Corporation Pvt Ltd for 5 years as Marketing Manager, leading a 10-person team."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Business (optional) -->
                        <h3 style="margin: 25px 0 15px; color: #2c3e50;">Business (optional)</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="NameOfBusiness">Name of Business</label>
                                <input type="text" id="NameOfBusiness" name="NameOfBusiness" maxlength="100" placeholder="ABC Marketing Solutions Pvt Ltd">
                            </div>
                            <div class="form-group">
                                <label for="AddressOfBusiness">Address of Business</label>
                                <input type="text" id="AddressOfBusiness" name="AddressOfBusiness" maxlength="150" placeholder="789 Duplication Road, Colombo 5">
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
                                <input type="text" id="OtherConnBusinessDesc" name="OtherConnBusinessDesc" maxlength="200" placeholder="Co-founder of ABC Digital Solutions Pvt Ltd, holding a 10% stake.">
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
                                <input type="text" id="BankAccountNo" name="BankAccountNo" maxlength="12" required placeholder="123456789123">
                                <div class="error-message">Please enter bank account number</div>
                            </div>

                            <div class="form-group">
                                <label for="BankCode" class="required">Bank Code</label>
                                <select id="BankCode" name="BankCode" required>
                                    <option value="">Select Bank</option>
                                </select>
                                <div class="error-message">Please select bank</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="BankBranch" class="required">Bank Branch</label>
                                <select id="BankBranch" name="BankBranch" required>
                                    <option value="">Select Branch</option>
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
                                <input type="text" id="UsaTaxIdentificationNo" name="UsaTaxIdentificationNo" maxlength="50" placeholder="123-45-6789">
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
                                <input type="text" id="DualCitizenCountry" name="DualCitizenCountry" maxlength="10" placeholder="United Kingdom">
                            </div>
                            <div class="form-group">
                                <label for="DualCitizenPassport">Dual Citizen Passport No</label>
                                <input type="text" id="DualCitizenPassport" name="DualCitizenPassport" maxlength="50" placeholder="UKP12345678">
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

                    <!-- Supporting Documents (Optional) -->
                    <div class="form-section">
                        <h2 class="section-title"><i class="fas fa-folder-open"></i> Supporting Documents <span class="optional-tag">(Optional)</span></h2>
                        <p class="supporting-docs-help">
                            Upload any of the following documents if available. Multiple files allowed per category.
                            Accepted formats: <strong>PDF, JPG, PNG</strong>. Max <strong>2MB</strong> per file.
                        </p>

                        <div id="supporting-docs-grid" class="supporting-docs-grid">
                            <?php foreach (get_supporting_doc_types() as $sdKey => $sdLabel): ?>
                            <div class="supporting-doc-card" data-supporting-key="<?= htmlspecialchars($sdKey) ?>" data-supporting-custom="0">
                                <div class="supporting-doc-card-head">
                                    <div class="supporting-doc-title"><?= htmlspecialchars($sdLabel) ?></div>
                                </div>
                                <label class="supporting-doc-picker">
                                    <input type="file" multiple accept="application/pdf,image/jpeg,image/png,image/gif,image/webp"
                                        class="supporting-doc-input" data-supporting-key="<?= htmlspecialchars($sdKey) ?>">
                                    <span class="supporting-doc-picker-btn"><i class="fas fa-plus"></i> Add files</span>
                                </label>
                                <ul class="supporting-doc-files" data-supporting-files-for="<?= htmlspecialchars($sdKey) ?>"></ul>
                                <div class="supporting-doc-error" style="display:none;"></div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="supporting-docs-actions">
                            <button type="button" id="add-custom-supporting-doc" class="btn btn-secondary">
                                <i class="fas fa-plus-circle"></i> Add Custom Document Type
                            </button>
                        </div>

                        <template id="supporting-doc-custom-template">
                            <div class="supporting-doc-card supporting-doc-card-custom" data-supporting-custom="1">
                                <div class="supporting-doc-card-head">
                                    <input type="text" class="supporting-doc-custom-label" placeholder="Document name (e.g. Marriage Certificate)" maxlength="80" required>
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

<div id="submission-progress-modal" class="submission-progress-overlay" role="dialog" aria-modal="true" aria-labelledby="submission-progress-title" aria-busy="false" aria-hidden="true">
    <div class="submission-progress-dialog">
        <div class="submission-progress-spinner" aria-hidden="true">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
        <h2 id="submission-progress-title" class="submission-progress-title" tabindex="-1">Account creation in progress</h2>
        <p class="submission-progress-warning">Please do not close or refresh this tab until the process finishes.</p>
        <p id="submission-progress-status" class="submission-progress-status">Submitting your application…</p>
    </div>
</div>
