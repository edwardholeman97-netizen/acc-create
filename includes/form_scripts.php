    <script>
        // ==================== MODE CONFIG ====================
        // window.__formConfig is set by the including page BEFORE this script.
        // Defaults to "create" mode if not provided (e.g., legacy callers).
        window.__formConfig = window.__formConfig || { mode: 'create', formData: {}, lockedKeys: [], token: null };

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
            applyResubmitMode();
        });

        // ==================== RESUBMIT MODE ====================
        function applyResubmitMode() {
            const cfg = window.__formConfig;
            // Pre-fill all fields from saved data (works for both modes; in create mode formData is empty).
            applyFormPrefill(cfg.formData || {});
            // Lock fields that must never change after first submission.
            applyLockedFields(cfg.lockedKeys || []);
            if (cfg.mode === 'resubmit') {
                const titleEl = document.getElementById('submission-progress-title');
                if (titleEl) titleEl.textContent = 'Resubmitting to CSE';
                const submitText = document.querySelector('#submit-btn .btn-text');
                if (submitText) submitText.innerHTML = '<i class="fas fa-paper-plane"></i> Submit to CSE';
            }
        }

        function applyFormPrefill(data) {
            if (!data || typeof data !== 'object') return;
            const form = document.getElementById('cdsAccountForm');
            if (!form) return;
            Object.keys(data).forEach(name => {
                const value = data[name];
                if (value === null || value === undefined) return;
                const radios = form.querySelectorAll('input[type="radio"][name="' + cssEscape(name) + '"]');
                if (radios.length) {
                    radios.forEach(r => { if (String(r.value) === String(value)) r.checked = true; });
                    return;
                }
                const checkboxes = form.querySelectorAll('input[type="checkbox"][name="' + cssEscape(name) + '"]');
                if (checkboxes.length) {
                    checkboxes.forEach(c => { c.checked = (String(c.value) === String(value)); });
                    return;
                }
                const el = form.querySelector('[name="' + cssEscape(name) + '"]');
                if (!el) return;
                if (el.tagName === 'SELECT') {
                    // If option not yet present (dynamic dropdown), inject it so the value sticks.
                    let found = Array.from(el.options).some(o => String(o.value) === String(value));
                    if (!found && String(value) !== '') {
                        const opt = document.createElement('option');
                        opt.value = String(value);
                        opt.textContent = String(value);
                        el.appendChild(opt);
                    }
                    el.value = String(value);
                } else if (el.type === 'file') {
                    // skip file inputs
                } else {
                    el.value = String(value);
                }
            });
            // Re-run conditional toggles so any pre-filled values reveal the right sections.
            try {
                toggleIdentificationFields();
                toggleEmploymentFields();
                togglePEPQuestions();
                toggleLitigationDetails();
                const cdsField = document.getElementById('cds-account-field');
                const cdsRadio = document.querySelector('input[name="ExitCDSAccount"]:checked');
                if (cdsField) cdsField.style.display = (cdsRadio && cdsRadio.value === 'Y') ? 'block' : 'none';
            } catch (e) { /* ignore */ }
        }

        function applyLockedFields(keys) {
            if (!Array.isArray(keys) || keys.length === 0) return;
            const form = document.getElementById('cdsAccountForm');
            if (!form) return;
            keys.forEach(key => {
                const els = form.querySelectorAll('[name="' + cssEscape(key) + '"]');
                els.forEach(el => {
                    el.readOnly = true;
                    if (el.tagName === 'SELECT' || el.type === 'radio' || el.type === 'checkbox') {
                        el.disabled = true;
                    }
                    const group = el.closest('.form-group');
                    if (group) {
                        group.classList.add('is-locked');
                        const label = group.querySelector('label');
                        if (label && !group.querySelector('.lock-icon')) {
                            const icon = document.createElement('i');
                            icon.className = 'fas fa-lock lock-icon';
                            icon.title = 'Locked - cannot be edited';
                            icon.style.marginLeft = '6px';
                            icon.style.color = '#9ca3af';
                            icon.style.fontSize = '11px';
                            label.appendChild(icon);
                        }
                    }
                });
            });
        }

        function cssEscape(str) {
            if (window.CSS && CSS.escape) return CSS.escape(str);
            return String(str).replace(/([!"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1');
        }

        // ==================== STEPWISE FORM FUNCTIONS ====================
        function initStepwiseForm() {
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

        function showStep(stepNumber) {
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });

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

            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
                if (parseInt(step.getAttribute('data-step')) === stepNumber) {
                    step.classList.add('active');
                }
            });

            document.getElementById('prev-btn').style.display = stepNumber === 1 ? 'none' : 'inline-block';
            document.getElementById('next-btn').style.display = stepNumber === totalSteps ? 'none' : 'inline-block';
            document.getElementById('submit-btn').style.display = stepNumber === totalSteps ? 'inline-block' : 'none';

            updateProgressBar();
        }

        function validateCurrentStep() {
            const currentStepElement = document.getElementById('step-' + currentStep);
            const requiredFields = currentStepElement.querySelectorAll('[required]');
            let isValid = true;
            const validatedRadioGroups = new Set();

            currentStepElement.querySelectorAll('.field-error').forEach(field => {
                field.classList.remove('field-error');
            });

            const idType = document.getElementById('IdentificationProof')?.value;
            requiredFields.forEach(field => {
                if (field.type === 'radio') {
                    if (validatedRadioGroups.has(field.name)) return;
                    validatedRadioGroups.add(field.name);
                }
                if (currentStep === 2 && field.id === 'NicNo' && idType === 'Passport') return;
                if (currentStep === 2 && field.id === 'PassportNo' && idType === 'NIC') return;
                if (!validateField(field)) {
                    isValid = false;
                }
            });

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

            // Step 7 (Documents): validate file size (2MB) and type (JPG/PNG); files optional in resubmit mode.
            if (currentStep === totalSteps) {
                const MAX_SIZE = 2 * 1024 * 1024;
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
            const dropdownSelects = [
                'BrokerFirm', 'ResAddressDistrict', 'Country',
                'CountryOfResidency', 'Nationality', 'BankCode', 'BankBranch'
            ];
            dropdownSelects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    // convertToEnhancedDropdown(select);
                }
            });
        }

        // ==================== API DATA LOADING ====================
        function loadDynamicData() {
            Promise.all([
                loadResource('getTitles', 'Title', 'TITLE_ID', 'TITLE_NAME', true),
                loadResource('getBrokers', 'BrokerFirm', 'BROKER_ID', 'BROKER_FULL_NAME', false, true),
                loadResource('getDistricts', 'ResAddressDistrict', 'DISTRICT_CODE', 'DISTRICT_NAME'),
                loadResource('getCountries', 'Country', 'COUNTRY_CODE', 'COUNTRY_NAME'),
                loadResource('getCountries', 'CountryOfResidency', 'COUNTRY_CODE', 'COUNTRY_NAME'),
                loadResource('getCountries', 'Nationality', 'COUNTRY_CODE', 'COUNTRY_NAME'),
                loadResource('getBanks', 'BankCode', 'BANK_CODE', 'BANK_NAME'),
                loadResource('getInvestAdvisors', 'InvestorId', 'INVESTOR_ID', 'INVESTOR_NAME', true)
            ]).then(() => {
                // After dynamic options are loaded, restore prefilled values that depend on them.
                const cfg = window.__formConfig;
                if (cfg && cfg.formData) {
                    applyFormPrefill(cfg.formData);
                    if (cfg.formData.BankCode) {
                        loadBankBranches(cfg.formData.BankBranch);
                    }
                    applyLockedFields(cfg.lockedKeys || []);
                }
            }).catch(error => {
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

            while (select.options.length > 1) {
                select.remove(1);
            }

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item[valueField];
                option.textContent = item[displayField] || item[valueField];
                select.appendChild(option);
            });
        }

        function loadBankBranches(preselect) {
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
                        if (preselect) {
                            branchSelect.value = preselect;
                        }
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

        // ==================== FORM SUBMISSION ====================
        function openSubmissionProgressModal(statusText) {
            const el = document.getElementById('submission-progress-modal');
            const statusEl = document.getElementById('submission-progress-status');
            const titleEl = document.getElementById('submission-progress-title');
            if (statusEl && statusText) statusEl.textContent = statusText;
            if (el) {
                el.classList.add('is-open');
                el.setAttribute('aria-hidden', 'false');
                el.setAttribute('aria-busy', 'true');
            }
            if (titleEl) titleEl.focus({ preventScroll: true });
        }

        function setSubmissionProgressStatus(text) {
            const statusEl = document.getElementById('submission-progress-status');
            if (statusEl && text) statusEl.textContent = text;
        }

        function closeSubmissionProgressModal() {
            const el = document.getElementById('submission-progress-modal');
            if (el) {
                el.classList.remove('is-open');
                el.setAttribute('aria-hidden', 'true');
                el.setAttribute('aria-busy', 'false');
            }
        }

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
                if (!el.name) continue;
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

            const cfg = window.__formConfig;
            if (cfg && cfg.mode === 'resubmit') {
                submitResubmitFlow();
            } else {
                submitCreateFlow();
            }
        }

        // Create flow: DB-only first save -> upload images keyed by submission_uid.
        function submitCreateFlow() {
            const form = document.getElementById('cdsAccountForm');
            const formDataObj = buildFormDataObject(form);

            setSubmitLoading(true);
            openSubmissionProgressModal('Submitting your application…');
            showStatus('Submitting your application for review...', 'info');

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
                    closeSubmissionProgressModal();
                    setSubmitLoading(false);
                    return Promise.resolve();
                }
                const submissionUid = data.submissionUid;

                const uploadFormData = new FormData();
                uploadFormData.append('step', 'upload');
                uploadFormData.append('submissionUid', submissionUid);
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

                const finishMsg = 'Submitted for review. We will email you once approved or if changes are required.';

                if (!hasFiles) {
                    setSubmissionProgressStatus('Finishing up…');
                    showStatus(finishMsg, 'success');
                    closeSubmissionProgressModal();
                    setSubmitLoading(false);
                    setTimeout(() => { resetForm(); goToStep(1); }, 5000);
                    return Promise.resolve();
                }

                setSubmissionProgressStatus('Uploading documents…');
                return fetch('api.php', { method: 'POST', body: uploadFormData })
                    .then(r => r.json())
                    .then(uploadData => {
                        showStatus(finishMsg, 'success');
                        closeSubmissionProgressModal();
                        setSubmitLoading(false);
                        setTimeout(() => { resetForm(); goToStep(1); }, 5000);
                    });
            })
            .catch(error => {
                showStatus('Network error: ' + error.message, 'error');
                closeSubmissionProgressModal();
                setSubmitLoading(false);
            });
        }

        // Resubmit flow (token-based): pushes directly to CSE in a single multipart request.
        function submitResubmitFlow() {
            const cfg = window.__formConfig;
            const form = document.getElementById('cdsAccountForm');
            const formDataObj = buildFormDataObject(form);

            setSubmitLoading(true);
            openSubmissionProgressModal('Submitting to CSE…');
            showStatus('Submitting your updated application to CSE...', 'info');

            const fd = new FormData();
            fd.append('step', 'client-resubmit');
            fd.append('token', cfg.token || '');
            fd.append('formData', JSON.stringify(formDataObj));
            const fileInputs = ['selfie_upload', 'nic_front_upload', 'nic_back_upload', 'passport_upload'];
            for (const name of fileInputs) {
                const input = form.querySelector(`[name="${name}"]`);
                if (input && input.files && input.files[0]) {
                    fd.append(name, input.files[0]);
                }
            }

            fetch('api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        showStatus('Error: ' + data.message, 'error');
                        showApiErrorsBelowFields(data.message);
                        closeSubmissionProgressModal();
                        setSubmitLoading(false);
                        return;
                    }
                    showStatus('Your application has been submitted to CSE. Account ID: ' + (data.accountId || ''), 'success');
                    closeSubmissionProgressModal();
                    setSubmitLoading(false);
                    setTimeout(() => {
                        const submitBtn = document.getElementById('submit-btn');
                        if (submitBtn) submitBtn.style.display = 'none';
                    }, 0);
                })
                .catch(error => {
                    showStatus('Network error: ' + error.message, 'error');
                    closeSubmissionProgressModal();
                    setSubmitLoading(false);
                });
        }

        // ==================== HELPER FUNCTIONS ====================
        function generateUserId() {
            return 'USER_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        }

        function setupDateDefaults() {
            const today = new Date().toISOString().split('T')[0];
            const enterDateField = document.getElementById('EnterDate');
            if (enterDateField && !enterDateField.value) enterDateField.value = today;

            const dobField = document.getElementById('DateOfBirthday');
            if (dobField && !dobField.value) {
                const defaultDob = new Date();
                defaultDob.setFullYear(defaultDob.getFullYear() - 25);
                dobField.valueAsDate = defaultDob;
            }
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
            document.addEventListener('blur', function(event) {
                if (event.target.matches('input, select, textarea')) {
                    validateField(event.target);
                }
            }, true);

            document.addEventListener('input', function(event) {
                if (event.target.matches('input, select, textarea')) {
                    clearFieldError(event.target);
                }
            });

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

            if (field.type === 'radio' && field.hasAttribute('required')) {
                const group = document.querySelectorAll(`input[type="radio"][name="${field.name}"]`);
                const hasSelection = Array.from(group).some(radio => radio.checked);
                if (!hasSelection) {
                    errorMessage = 'Please select an option';
                    isValid = false;
                }
            }

            if (field.type !== 'radio' && field.hasAttribute('required') && !field.value.trim()) {
                errorMessage = 'This field is required';
                isValid = false;
            }

            if (field.type === 'email' && field.value && !isValidEmail(field.value)) {
                errorMessage = 'Please enter a valid email address';
                isValid = false;
            }

            if ((field.id === 'MobileNo' || field.id === 'TelphoneNo') && field.value && !isValidPhone(field.value)) {
                errorMessage = 'Please enter a valid phone number';
                isValid = false;
            }

            if (field.id === 'NicNo' && field.value && !isValidNIC(field.value)) {
                errorMessage = 'Please enter a valid NIC number';
                isValid = false;
            }

            if (field.type === 'date' && field.id === 'DateOfBirthday' && field.value) {
                const inputDate = new Date(field.value);
                const today = new Date();
                if (inputDate > today) {
                    errorMessage = 'Date of birth cannot be in the future';
                    isValid = false;
                }
            }

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

        function isValidPhone(phone) {
            const phoneRegex = /^[\d\s\+\-\(\)]{10,16}$/;
            return phoneRegex.test(phone);
        }

        function isValidNIC(nic) {
            const oldNicRegex = /^[0-9]{9}[VX]$/i;
            const newNicRegex = /^[0-9]{12}$/;
            return oldNicRegex.test(nic) || newNicRegex.test(nic);
        }

        // ==================== CONDITIONAL FIELD FUNCTIONS ====================
        function setupConditionalFields() {
            toggleIdentificationFields();
            toggleEmploymentFields();
            togglePEPQuestions();
            toggleLitigationDetails();

            document.getElementById('IdentificationProof')?.addEventListener('change', toggleIdentificationFields);
            document.getElementById('EmployeStatus')?.addEventListener('change', toggleEmploymentFields);

            document.querySelectorAll('input[name="IsPEP"]').forEach(radio => {
                radio.addEventListener('change', togglePEPQuestions);
            });

            document.querySelectorAll('input[name="LitigationStatus"]').forEach(radio => {
                radio.addEventListener('change', toggleLitigationDetails);
            });

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
                const visible = ['Y', 'S'].includes(employmentStatus);
                if (visible) {
                    employmentDetails.classList.add('active');
                } else {
                    employmentDetails.classList.remove('active');
                }
                setConditionalRequired(employmentDetails, visible);
            }
        }

        function setConditionalRequired(container, isVisible) {
            if (!container) return;
            container.querySelectorAll('input, select, textarea').forEach(el => {
                if (isVisible) {
                    if (el.dataset.wasRequired === '1') {
                        el.required = true;
                    }
                } else {
                    if (el.required) {
                        el.dataset.wasRequired = '1';
                    }
                    el.required = false;
                }
            });
        }

        function togglePEPQuestions() {
            const isPEP = document.querySelector('input[name="IsPEP"]:checked');
            const pepQuestions = document.getElementById('pep-questions');

            if (pepQuestions) {
                const visible = !!(isPEP && isPEP.value === 'Y');
                if (visible) {
                    pepQuestions.classList.add('active');
                } else {
                    pepQuestions.classList.remove('active');
                }
                setConditionalRequired(pepQuestions, visible);
            }
        }

        function toggleLitigationDetails() {
            const litigationStatus = document.querySelector('input[name="LitigationStatus"]:checked');
            const litigationDetails = document.getElementById('litigation-details');

            if (litigationDetails) {
                const visible = !!(litigationStatus && litigationStatus.value === 'Y');
                if (visible) {
                    litigationDetails.classList.add('active');
                } else {
                    litigationDetails.classList.remove('active');
                }
                setConditionalRequired(litigationDetails, visible);
            }
        }

        // ==================== IMAGE FUNCTIONS ====================
        function previewImage(input, previewId) {
            const file = input.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                showFieldError(input, 'File size must be less than 2MB');
                input.value = '';
                return;
            }

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
