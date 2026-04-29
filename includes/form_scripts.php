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
            initSupportingDocs();
            applyResubmitMode();
        });

        // ==================== SUPPORTING DOCUMENTS ====================
        // Optional client-supplied docs (utility bills, bank statements, custom
        // categories, etc.). Stored on our server only — never pushed to CSE.
        const SUPPORTING_DOC_MAX_SIZE = 2 * 1024 * 1024; // 2MB
        const SUPPORTING_DOC_ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
        // Each predefined entry: { existing: [{id,name,size,mime,path}], pending: [File] }
        const supportingDocs = { predefined: {}, custom: [] };
        const supportingRemoveIds = [];
        let customSupportingCounter = 0;

        function initSupportingDocs() {
            const grid = document.getElementById('supporting-docs-grid');
            if (!grid) return;

            grid.querySelectorAll('.supporting-doc-card').forEach(card => {
                const key = card.getAttribute('data-supporting-key');
                if (!key) return;
                supportingDocs.predefined[key] = supportingDocs.predefined[key] || { existing: [], pending: [] };
                const input = card.querySelector('.supporting-doc-input');
                if (input) {
                    input.addEventListener('change', function() {
                        addSupportingFiles(card, supportingDocs.predefined[key], this.files);
                        this.value = '';
                    });
                }
            });

            const addBtn = document.getElementById('add-custom-supporting-doc');
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    addCustomSupportingCard();
                });
            }

            // Hydrate from window.__formConfig.supportingDocs (resubmit flow only).
            const cfg = window.__formConfig || {};
            if (cfg.supportingDocs && Array.isArray(cfg.supportingDocs.categories)) {
                cfg.supportingDocs.categories.forEach(cat => {
                    if (!cat.custom) {
                        const entry = supportingDocs.predefined[cat.id];
                        const card = grid.querySelector('.supporting-doc-card[data-supporting-key="' + cssEscape(cat.id) + '"]');
                        if (entry && card) {
                            entry.existing = (cat.files || []).map(f => Object.assign({}, f));
                            renderSupportingCard(card, entry);
                        }
                    } else {
                        const card = addCustomSupportingCard(cat.label || 'Other Document', cat.id || '');
                        if (card) {
                            const entry = supportingDocs.custom.find(c => c.localId === card.dataset.customLocalId);
                            if (entry) {
                                entry.serverId = cat.id || '';
                                entry.existing = (cat.files || []).map(f => Object.assign({}, f));
                                renderSupportingCard(card, entry);
                            }
                        }
                    }
                });
            }

            // Expose remove-ids list to the resubmit flow.
            window.__supportingRemoveIds = supportingRemoveIds;
        }

        function addCustomSupportingCard(initialLabel, serverId) {
            const tpl = document.getElementById('supporting-doc-custom-template');
            const grid = document.getElementById('supporting-docs-grid');
            if (!tpl || !grid) return null;
            const node = tpl.content.firstElementChild.cloneNode(true);
            const localIdx = customSupportingCounter++;
            const customEntry = {
                localId: 'custom_' + Date.now() + '_' + localIdx,
                label: initialLabel || '',
                serverId: serverId || '',
                existing: [],
                pending: []
            };
            supportingDocs.custom.push(customEntry);
            node.dataset.customLocalId = customEntry.localId;

            const labelInput = node.querySelector('.supporting-doc-custom-label');
            if (labelInput) {
                if (initialLabel) labelInput.value = initialLabel;
                labelInput.addEventListener('input', function() {
                    customEntry.label = this.value;
                    clearCardError(node);
                });
            }
            const fileInput = node.querySelector('.supporting-doc-input');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    addSupportingFiles(node, customEntry, this.files);
                    this.value = '';
                });
            }
            const removeBtn = node.querySelector('.supporting-doc-remove-card');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    // Mark all existing files for deletion then drop the card.
                    customEntry.existing.forEach(f => { if (f.id) supportingRemoveIds.push(f.id); });
                    const idx = supportingDocs.custom.findIndex(c => c.localId === customEntry.localId);
                    if (idx !== -1) supportingDocs.custom.splice(idx, 1);
                    node.remove();
                });
            }
            grid.appendChild(node);
            if (labelInput && !initialLabel) labelInput.focus();
            return node;
        }

        function addSupportingFiles(card, entry, fileList) {
            if (!fileList || !fileList.length) return;
            clearCardError(card);
            const errors = [];
            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                const errMsg = validateSupportingFile(file);
                if (errMsg) {
                    errors.push(file.name + ': ' + errMsg);
                    continue;
                }
                if (entry.pending.some(f => f.name === file.name && f.size === file.size)) continue;
                entry.pending.push(file);
            }
            renderSupportingCard(card, entry);
            if (errors.length) showCardError(card, errors.join('. '));
        }

        function validateSupportingFile(file) {
            if (file.size <= 0 || file.size > SUPPORTING_DOC_MAX_SIZE) return 'exceeds 2MB';
            const dot = file.name.lastIndexOf('.');
            const ext = (dot === -1) ? '' : file.name.slice(dot + 1).toLowerCase();
            if (!SUPPORTING_DOC_ALLOWED_EXT.includes(ext)) return 'unsupported file type';
            return null;
        }

        // Cache of object URLs created for pending File previews so we can revoke them on re-render.
        const supportingPendingUrls = new WeakMap();

        function isImageDoc(nameOrMime) {
            if (!nameOrMime) return false;
            if (/^image\//i.test(nameOrMime)) return true;
            return /\.(jpe?g|png|gif|webp)$/i.test(nameOrMime);
        }

        function buildExistingDocUrl(file) {
            // Resubmit flow: route through the public token-gated viewer.
            const cfg = window.__formConfig || {};
            if (cfg.mode === 'resubmit' && cfg.token && file && file.id) {
                return 'view_supporting.php?token=' + encodeURIComponent(cfg.token)
                    + '&fid=' + encodeURIComponent(file.id);
            }
            return '';
        }

        function buildSupportingFileItem(file, opts) {
            // opts: { existing: bool, onRemove: fn, viewUrl: string|null }
            const li = document.createElement('li');
            li.className = 'supporting-doc-file' + (opts.existing ? ' supporting-doc-file-existing' : '');

            const fileName = file.name || 'document';
            const fileSize = humanFileSize(file.size || 0);
            const mime = file.mime || file.type || '';
            const isImg = isImageDoc(mime) || isImageDoc(fileName);

            // Thumbnail (image preview) or icon
            const thumb = document.createElement('span');
            thumb.className = 'supporting-doc-thumb' + (isImg ? '' : ' is-icon');
            if (isImg) {
                const img = document.createElement('img');
                img.alt = fileName;
                img.loading = 'lazy';
                if (opts.existing && opts.viewUrl) {
                    img.src = opts.viewUrl;
                } else if (file instanceof Blob) {
                    try {
                        const u = URL.createObjectURL(file);
                        supportingPendingUrls.set(file, u);
                        img.src = u;
                    } catch (e) { /* ignore */ }
                }
                thumb.appendChild(img);
            } else {
                const i = document.createElement('i');
                i.className = 'fas fa-file-pdf';
                thumb.appendChild(i);
            }

            // Filename — clickable when we have a URL.
            const nameWrap = document.createElement('span');
            nameWrap.className = 'supporting-doc-meta';
            let nameEl;
            const linkHref = opts.existing
                ? (opts.viewUrl || '')
                : (file instanceof Blob ? (URL.createObjectURL(file)) : '');
            if (linkHref) {
                nameEl = document.createElement('a');
                nameEl.href = linkHref;
                nameEl.target = '_blank';
                nameEl.rel = 'noopener';
                nameEl.className = 'file-name file-name-link';
                if (!opts.existing) supportingPendingUrls.set(file, linkHref);
            } else {
                nameEl = document.createElement('span');
                nameEl.className = 'file-name';
            }
            nameEl.textContent = fileName;
            const sizeEl = document.createElement('span');
            sizeEl.className = 'file-size';
            sizeEl.textContent = fileSize;
            nameWrap.appendChild(nameEl);
            nameWrap.appendChild(sizeEl);

            const rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'file-remove';
            rmBtn.title = 'Remove';
            rmBtn.innerHTML = '<i class="fas fa-times"></i>';
            rmBtn.addEventListener('click', opts.onRemove);

            li.appendChild(thumb);
            li.appendChild(nameWrap);
            li.appendChild(rmBtn);
            return li;
        }

        function renderSupportingCard(card, entry) {
            const ul = card.querySelector('.supporting-doc-files');
            if (!ul) return;
            // Revoke any previous blob URLs we created for pending files in this card.
            ul.querySelectorAll('img[src^="blob:"], a[href^="blob:"]').forEach(el => {
                const u = el.src || el.href;
                if (u) { try { URL.revokeObjectURL(u); } catch (e) {} }
            });
            ul.innerHTML = '';

            // Existing (already on server) — clickable via token-gated viewer (resubmit flow).
            entry.existing.forEach((file, idx) => {
                const li = buildSupportingFileItem(file, {
                    existing: true,
                    viewUrl: buildExistingDocUrl(file),
                    onRemove: function() {
                        if (file.id) supportingRemoveIds.push(file.id);
                        entry.existing.splice(idx, 1);
                        renderSupportingCard(card, entry);
                    }
                });
                ul.appendChild(li);
            });

            // Pending (newly picked, in memory) — preview via blob URL.
            entry.pending.forEach((file, idx) => {
                const li = buildSupportingFileItem(file, {
                    existing: false,
                    onRemove: function() {
                        const u = supportingPendingUrls.get(file);
                        if (u) { try { URL.revokeObjectURL(u); } catch (e) {} }
                        entry.pending.splice(idx, 1);
                        renderSupportingCard(card, entry);
                    }
                });
                ul.appendChild(li);
            });
        }

        function humanFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(2) + ' MB';
        }

        function showCardError(card, msg) {
            const el = card.querySelector('.supporting-doc-error');
            if (el) { el.textContent = msg; el.style.display = 'block'; }
        }

        function clearCardError(card) {
            const el = card.querySelector('.supporting-doc-error');
            if (el) { el.textContent = ''; el.style.display = 'none'; }
        }

        // Append all in-memory NEW supporting files (+ a JSON manifest) onto a
        // FormData. Existing server-side files are not re-uploaded; removals
        // travel separately via window.__supportingRemoveIds.
        function appendSupportingDocsToFormData(fd) {
            const meta = [];

            Object.keys(supportingDocs.predefined).forEach(key => {
                const entry = supportingDocs.predefined[key];
                if (!entry || !entry.pending.length) return;
                const fieldName = 'supporting_' + key;
                meta.push({ id: key, label: '', custom: false, field: fieldName });
                entry.pending.forEach(f => fd.append(fieldName + '[]', f, f.name));
            });

            supportingDocs.custom.forEach((entry, idx) => {
                if (!entry.pending.length) return;
                const label = (entry.label || '').trim();
                if (!label) return;
                const fieldName = 'supporting_custom_' + idx + '_' + Date.now();
                meta.push({ id: entry.serverId || '', label: label, custom: true, field: fieldName });
                entry.pending.forEach(f => fd.append(fieldName + '[]', f, f.name));
            });

            if (meta.length) {
                fd.append('supporting_meta', JSON.stringify(meta));
                return true;
            }
            return false;
        }

        function validateCustomSupportingLabels() {
            let ok = true;
            document.querySelectorAll('#supporting-docs-grid .supporting-doc-card-custom').forEach(card => {
                const localId = card.dataset.customLocalId;
                const entry = supportingDocs.custom.find(c => c.localId === localId);
                if (!entry) return;
                if ((entry.pending.length || entry.existing.length) && !(entry.label || '').trim()) {
                    showCardError(card, 'Please enter a name for this document type.');
                    const inp = card.querySelector('.supporting-doc-custom-label');
                    if (inp) inp.focus();
                    ok = false;
                }
            });
            return ok;
        }

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
            if (!validateCustomSupportingLabels()) return;
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
                const hasSupporting = appendSupportingDocsToFormData(uploadFormData);
                hasFiles = hasFiles || hasSupporting;

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
            appendSupportingDocsToFormData(fd);
            // Apply any per-file removals captured by the client edit-link flow.
            (window.__supportingRemoveIds || []).forEach(id => fd.append('supporting_remove[]', id));

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

                const _label = imageTypeMap[input.id] || 'Document';
                previewDiv.innerHTML = `
            <a href="${e.target.result}" target="_blank" rel="noopener" title="Open full size" class="preview-img-link">
                <img src="${e.target.result}" class="preview-img" alt="${_label}">
            </a>
            <p>${_label}</p>
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
