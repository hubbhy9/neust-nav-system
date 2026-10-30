<?php
header('Content-Type: application/javascript');
?>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('document-steps-container');
    const addButton = document.getElementById('add-document-step');
    const clearButton = document.getElementById('clear-document-form');
    const requirementsContainer = document.getElementById('requirements-container');
    const requirementCountInput = document.getElementById('requirement-count');
    const addRequirementButton = document.getElementById('add-requirement-row');
    const transactionTypesContainer = document.getElementById('transaction-types-container');
    const transactionTypeCountInput = document.getElementById('transaction-type-count');
    const totalFeesInput = document.getElementById('total-fees');
    const totalTimeInput = document.getElementById('total-time');
    const totalFeesHiddenInput = document.getElementById('total-fees-hidden');
    const totalTimeHiddenInput = document.getElementById('total-time-hidden');
    const toggleTotalManualButton = document.getElementById('toggle-total-manual');
    const transactionTypeOptions = Array.isArray(window.documentTransactionTypes)
        ? window.documentTransactionTypes
        : [];

    window.documentSubmittedSteps = Array.isArray(window.documentSubmittedSteps) ? window.documentSubmittedSteps : [];
    window.documentExistingRequirements = Array.isArray(window.documentExistingRequirements) ? window.documentExistingRequirements : [];
    window.documentSubmittedTransactionOptions = Array.isArray(window.documentSubmittedTransactionOptions) ? window.documentSubmittedTransactionOptions : [];
    window.documentSubmittedRequirementCount = Number(window.documentSubmittedRequirementCount) || 1;
    window.documentSubmittedTransactionTypeCount = Number(window.documentSubmittedTransactionTypeCount) || 1;
    window.documentSubmittedStepCount = Number(window.documentSubmittedStepCount) || window.documentSubmittedSteps.length || 1;
    window.documentExistingTotals = Array.isArray(window.documentExistingTotals) ? window.documentExistingTotals : [];

    const normalizeCurrencyValue = (value) => {
        const text = (value || '').toString().trim();
        if (!text || text.toLowerCase() === 'none') {
            return text;
        }

        const plainNumberPattern = /^\s*(?:₱|P)?\s*\d[\d,]*(?:\.\d+)?\s*$/;
        if (plainNumberPattern.test(text) && !text.includes('₱') && !text.toUpperCase().startsWith('P')) {
            return `₱${text}`;
        }

        return text;
    };

    if (totalFeesInput && window.documentExistingTotals.length) {
        const savedFeeValue = (window.documentExistingTotals[0]?.fees ?? '').toString().trim();
        if (savedFeeValue !== '') {
            totalFeesInput.value = normalizeCurrencyValue(savedFeeValue);
        }
    }

    if (totalTimeInput && window.documentExistingTotals.length) {
        const savedTimeValue = (window.documentExistingTotals[0]?.time ?? '').toString().trim();
        if (savedTimeValue !== '') {
            totalTimeInput.value = savedTimeValue;
        }
    }

    if (!container || !addButton) return;

    let manualTotalsEnabled = false;
    let initialTotalsSeeded = false;

    const syncTotalHiddenInputs = () => {
        if (totalFeesInput && totalFeesHiddenInput) {
            totalFeesHiddenInput.value = totalFeesInput.value;
        }

        if (totalTimeInput && totalTimeHiddenInput) {
            totalTimeHiddenInput.value = totalTimeInput.value;
        }
    };

    const validateStepSequence = () => {
        const rows = Array.from(container.querySelectorAll('.document-step-row'));
        let previousStep = null;
        let isValid = true;

        rows.forEach((row) => {
            const input = row.querySelector('.step-number-input');
            if (!input) {
                return;
            }

            let stepValue = Number.parseInt(input.value, 10);
            if (!Number.isFinite(stepValue) || stepValue < 1) {
                stepValue = 1;
                input.value = '1';
            }

            if (previousStep !== null) {
                if (stepValue < previousStep) {
                    stepValue = previousStep;
                    input.value = String(stepValue);
                }

                if (stepValue > previousStep + 1) {
                    stepValue = previousStep + 1;
                    input.value = String(stepValue);
                }
            }

            if (previousStep === null || stepValue >= previousStep) {
                input.setCustomValidity('');
            } else {
                input.setCustomValidity('Step numbers must stay the same or increase by only 1 each time. Example: 1, 1, 2 is valid, but 1, 1, 3 is not.');
                isValid = false;
            }

            previousStep = stepValue;
        });

        if (!isValid) {
            const firstInvalid = container.querySelector('.step-number-input:invalid');
            firstInvalid?.reportValidity?.();
        }

        return isValid;
    };

    const parseFeeAmount = (value) => {
        const text = (value || '').toString().trim();
        if (!text || text.toLowerCase() === 'none') {
            return 0;
        }

        const matches = text.match(/\d+(?:,\d{3})*(?:\.\d+)?/g) || [];
        if (!matches.length) {
            return 0;
        }

        return matches.reduce((sum, match) => {
            return sum + parseFloat(match.replace(/,/g, ''));
        }, 0);
    };

    const parseTimeToMinutes = (value) => {
        const text = (value || '').toString().trim().toLowerCase();
        if (!text || text === 'none') {
            return 0;
        }

        let totalMinutes = 0;

        const weekMatch = text.match(/(\d+(?:\.\d+)?)\s*(week|weeks|wk|wks)\b/);
        if (weekMatch) {
            totalMinutes += parseFloat(weekMatch[1]) * 7 * 24 * 60;
        }

        const dayMatch = text.match(/(\d+(?:\.\d+)?)\s*(day|days)\b/);
        if (dayMatch) {
            totalMinutes += parseFloat(dayMatch[1]) * 24 * 60;
        }

        const hourMatch = text.match(/(\d+(?:\.\d+)?)\s*(hour|hours|hr|hrs)\b/);
        if (hourMatch) {
            totalMinutes += parseFloat(hourMatch[1]) * 60;
        }

        const minuteMatch = text.match(/(\d+(?:\.\d+)?)\s*(minute|minutes|min|mins)\b/);
        if (minuteMatch) {
            totalMinutes += parseFloat(minuteMatch[1]);
        }

        if (totalMinutes === 0) {
            const fallback = text.match(/\d+/g);
            if (fallback && fallback.length) {
                totalMinutes = parseInt(fallback[0], 10) || 0;
            }
        }

        return Math.round(totalMinutes);
    };

    const formatCurrency = (value) => {
        const numericValue = Number(value) || 0;
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(numericValue);
    };

    const formatMinutes = (totalMinutes) => {
        const safeTotal = Math.max(0, Math.round(Number(totalMinutes) || 0));
        if (safeTotal === 0) {
            return '0 minutes';
        }

        const weeks = Math.floor(safeTotal / (7 * 24 * 60));
        const days = Math.floor((safeTotal % (7 * 24 * 60)) / (24 * 60));
        const hours = Math.floor((safeTotal % (24 * 60)) / 60);
        const minutes = safeTotal % 60;
        const parts = [];

        if (weeks > 0) {
            parts.push(`${weeks} week${weeks > 1 ? 's' : ''}`);
        }
        if (days > 0) {
            parts.push(`${days} day${days > 1 ? 's' : ''}`);
        }
        if (hours > 0) {
            parts.push(`${hours} hour${hours > 1 ? 's' : ''}`);
        }
        if (minutes > 0 || parts.length === 0) {
            parts.push(`${minutes} minute${minutes === 1 ? '' : 's'}`);
        }

        return parts.join(' ');
    };

    const applyTotalsState = () => {
        const isReadonly = !manualTotalsEnabled;

        if (totalFeesInput) {
            totalFeesInput.readOnly = isReadonly;
            totalFeesInput.classList.toggle('readonly-field', isReadonly);
        }

        if (totalTimeInput) {
            totalTimeInput.readOnly = isReadonly;
            totalTimeInput.classList.toggle('readonly-field', isReadonly);
        }

        if (toggleTotalManualButton) {
            toggleTotalManualButton.classList.toggle('btn-warning', manualTotalsEnabled);
            toggleTotalManualButton.classList.toggle('btn-outline-secondary', !manualTotalsEnabled);
            toggleTotalManualButton.textContent = manualTotalsEnabled ? 'Manual Editing On' : 'Set Manually';
        }

        if (!manualTotalsEnabled && (!Array.isArray(window.documentExistingTotals) || !window.documentExistingTotals.length)) {
            recalculateTotals();
        }
    };

    const recalculateTotals = () => {
        if (!totalFeesInput || !totalTimeInput || manualTotalsEnabled) {
            return;
        }

        if (Array.isArray(window.documentExistingTotals) && window.documentExistingTotals.length) {
            const savedTotalFees = (window.documentExistingTotals[0]?.fees ?? '').toString().trim();
            const savedTotalTime = (window.documentExistingTotals[0]?.time ?? '').toString().trim();

            if (savedTotalFees !== '') {
                totalFeesInput.value = normalizeCurrencyValue(savedTotalFees);
            }

            if (savedTotalTime !== '') {
                totalTimeInput.value = savedTotalTime;
            }

            syncTotalHiddenInputs();
            return;
        }

        let totalFees = 0;
        let totalMinutes = 0;

        document.querySelectorAll('.document-step-row').forEach((row) => {
            const feeTextArea = row.querySelectorAll('textarea')[2];
            const timeTextArea = row.querySelectorAll('textarea')[3];

            if (feeTextArea) {
                totalFees += parseFeeAmount(feeTextArea.value);
            }

            if (timeTextArea) {
                totalMinutes += parseTimeToMinutes(timeTextArea.value);
            }
        });

        totalFeesInput.value = formatCurrency(totalFees);
        totalTimeInput.value = formatMinutes(totalMinutes);
        syncTotalHiddenInputs();
    };

    const bindStepRowEvents = (row) => {
        const feeField = row.querySelectorAll('textarea')[2];
        const timeField = row.querySelectorAll('textarea')[3];

        feeField?.addEventListener('input', function () {
            feeField.value = normalizeCurrencyValue(feeField.value);
            recalculateTotals();
        });
        timeField?.addEventListener('input', recalculateTotals);
    };

    totalFeesInput?.addEventListener('input', function () {
        totalFeesInput.value = normalizeCurrencyValue(totalFeesInput.value);
        syncTotalHiddenInputs();
    });
    totalTimeInput?.addEventListener('input', syncTotalHiddenInputs);

    if (toggleTotalManualButton) {
        toggleTotalManualButton.addEventListener('click', function () {
            manualTotalsEnabled = !manualTotalsEnabled;
            applyTotalsState();
        });
    }

    const getRequirementCount = () => {
        const value = Number(requirementCountInput?.value) || Number(window.documentSubmittedRequirementCount) || 1;
        return Math.max(1, Number.isFinite(value) ? value : 1);
    };

    const getTransactionTypeCount = () => {
        const value = Number(transactionTypeCountInput?.value) || Number(window.documentSubmittedTransactionTypeCount) || 1;
        return Math.max(1, Number.isFinite(value) ? value : 1);
    };

    const getStepCount = () => {
        const countFromSteps = Array.isArray(window.documentSubmittedSteps) ? window.documentSubmittedSteps.length : 0;
        const value = Number(window.documentSubmittedStepCount) || countFromSteps || 1;
        return Math.max(1, Number.isFinite(value) ? value : 1);
    };

    const renderRequirementFields = (count) => {
        if (!requirementsContainer) return;

        const safeCount = Math.max(1, Number(count) || getRequirementCount());
        const existingRequirements = Array.isArray(window.documentExistingRequirements)
            ? window.documentExistingRequirements
            : [];
        requirementsContainer.innerHTML = '';

        for (let i = 1; i <= safeCount; i++) {
            const row = document.createElement('div');
            row.className = 'row g-3 mb-3 requirement-row';
            const requirementData = existingRequirements[i - 1] || {};
            const requirementValue = requirementData.requirement || '';
            const secureValue = requirementData.secure || '';

            row.innerHTML = `
                <div class="col-md-6">
                    <label class="form-label">Requirement ${i}</label>
                    <textarea class="form-control" rows="2" name="requirements[]" placeholder="List required document or checklist item">${escapeHtml(requirementValue)}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Where to Secure ${i}</label>
                    <textarea class="form-control" rows="2" name="where_to_secure[]" placeholder="Where the client can secure this requirement">${escapeHtml(secureValue)}</textarea>
                </div>
            `;
            requirementsContainer.appendChild(row);
        }
    };

    const addRequirementRow = () => {
        if (!requirementsContainer) return;

        const currentCount = requirementsContainer.querySelectorAll('.requirement-row').length;
        const nextCount = Math.max(1, currentCount + 1);
        if (requirementCountInput) {
            requirementCountInput.value = String(nextCount);
        }
        renderRequirementFields(nextCount);
    };

    const restoreSubmittedSteps = () => {
        if (!container) return;

        const submittedSteps = Array.isArray(window.documentSubmittedSteps)
            ? window.documentSubmittedSteps
            : [];

        if (!submittedSteps.length) {
            return;
        }

        container.innerHTML = '';
        submittedSteps.forEach((stepData, index) => {
            const row = createStepRow();
            const stepInput = row.querySelector('.step-number-input');
            if (stepInput) {
                stepInput.value = stepData.step_number || String(index + 1);
            }

            const clientField = row.querySelector('textarea[name="client_step[]"]');
            if (clientField) clientField.value = stepData.client_step || '';

            const agencyField = row.querySelector('textarea[name="agency_action[]"]');
            if (agencyField) agencyField.value = stepData.agency_action || '';

            const feeField = row.querySelector('textarea[name="fees[]"]');
            if (feeField) feeField.value = stepData.fees || '';

            const timeField = row.querySelector('textarea[name="processing_time[]"]');
            if (timeField) timeField.value = stepData.processing_time || '';

            const personField = row.querySelector('textarea[name="person_responsible[]"]');
            if (personField) personField.value = stepData.person_responsible || '';

            container.appendChild(row);
        });
    };

    const setupOtherFieldToggle = (select, wrap) => {
        if (!select || !wrap) return;

        const input = wrap.querySelector('input');
        if (!input) return;

        const sync = () => {
            const isEnabled = select.value === 'other';
            input.disabled = !isEnabled;
            input.setAttribute('aria-disabled', String(!isEnabled));
            input.style.opacity = isEnabled ? '1' : '0.75';
            input.style.cursor = isEnabled ? 'text' : 'not-allowed';

            if (!isEnabled) {
                input.value = '';
            }
        };

        select.addEventListener('change', sync);
        sync();
    };

    const escapeHtml = (value) => {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const renderTransactionTypeFields = (count) => {
        if (!transactionTypesContainer) return;

        const limit = Number(window.documentTransactionTypeLimit);
        const maxAllowed = Number.isFinite(limit) && limit > 0 ? limit : Number.MAX_SAFE_INTEGER;
        const safeCount = Math.max(1, Math.min(maxAllowed, Number(count) || getTransactionTypeCount()));
        const existingTransactionIds = Array.isArray(window.documentExistingTransactionIds)
            ? window.documentExistingTransactionIds.map(String)
            : [];
        const submittedTransactionOptions = Array.isArray(window.documentSubmittedTransactionOptions)
            ? window.documentSubmittedTransactionOptions
            : [];
        transactionTypesContainer.innerHTML = '';

        for (let i = 1; i <= safeCount; i++) {
            const wrapper = document.createElement('div');
            wrapper.className = 'transaction-type-entry';

            const row = document.createElement('div');
            row.className = 'other-field-pair';

            const selectWrap = document.createElement('div');
            selectWrap.className = 'transaction-select-wrap';

            const selectLabel = document.createElement('label');
            selectLabel.className = 'form-label';
            selectLabel.textContent = `Transaction Type ${i}`;

            const select = document.createElement('select');
            select.className = 'form-select';
            select.name = 'transaction_types[]';

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '-- Select Transaction Type --';
            select.appendChild(defaultOption);

            transactionTypeOptions.forEach((type) => {
                const option = document.createElement('option');
                option.value = String(type.id);
                option.textContent = type.name;
                select.appendChild(option);
            });

            const otherOption = document.createElement('option');
            otherOption.value = 'other';
            otherOption.textContent = 'Other';
            select.appendChild(otherOption);

            const selectedId = existingTransactionIds[i - 1] || (submittedTransactionOptions[i - 1]?.selected || '');
            if (selectedId) {
                select.value = selectedId;
            }

            selectWrap.appendChild(selectLabel);
            selectWrap.appendChild(select);

            const otherWrap = document.createElement('div');
            otherWrap.className = 'transaction-custom-wrap';
            const customInput = document.createElement('input');
            customInput.type = 'text';
            customInput.className = 'form-control';
            customInput.name = 'transaction_type_other[]';
            customInput.placeholder = 'Enter transaction type name';
            const submittedCustomValue = submittedTransactionOptions[i - 1]?.custom || '';
            if (submittedCustomValue) {
                customInput.value = submittedCustomValue;
            }

            const otherLabel = document.createElement('label');
            otherLabel.className = 'form-label';
            otherLabel.textContent = 'Specify Transaction Type';
            otherWrap.appendChild(otherLabel);
            otherWrap.appendChild(customInput);

            row.appendChild(selectWrap);
            row.appendChild(otherWrap);
            wrapper.appendChild(row);
            transactionTypesContainer.appendChild(wrapper);

            setupOtherFieldToggle(select, otherWrap);
        }
    };

    if (requirementCountInput) {
        requirementCountInput.addEventListener('input', function () {
            const value = Number(this.value) || 1;
            this.value = String(Math.max(1, value));
            renderRequirementFields(this.value);
        });
    }

    if (addRequirementButton) {
        addRequirementButton.addEventListener('click', addRequirementRow);
    }

    if (transactionTypeCountInput) {
        transactionTypeCountInput.addEventListener('input', function () {
            const limit = Number(window.documentTransactionTypeLimit);
            const maxAllowed = Number.isFinite(limit) && limit > 0 ? limit : Number.MAX_SAFE_INTEGER;
            const value = Number(this.value) || 1;
            this.value = String(Math.max(1, Math.min(maxAllowed, value)));
            renderTransactionTypeFields(this.value);
        });
    }

    if (requirementCountInput && Number(requirementCountInput.value) > 0) {
        requirementCountInput.value = String(getRequirementCount());
    }

    if (transactionTypeCountInput && Number(transactionTypeCountInput.value) > 0) {
        transactionTypeCountInput.value = String(getTransactionTypeCount());
    }

    const officeSelect = document.getElementById('office-select');
    const officeOtherWrap = document.getElementById('office-other-wrap');
    const classificationSelect = document.getElementById('classification-select');
    const classificationOtherWrap = document.getElementById('classification-other-wrap');

    if (requirementCountInput && requirementCountInput.value) {
        const count = Number(requirementCountInput.value) || 1;
        requirementCountInput.value = String(Math.max(1, count));
    }

    if (transactionTypeCountInput && transactionTypeCountInput.value) {
        const count = Number(transactionTypeCountInput.value) || 1;
        transactionTypeCountInput.value = String(Math.max(1, count));
    }

    setupOtherFieldToggle(officeSelect, officeOtherWrap);
    setupOtherFieldToggle(classificationSelect, classificationOtherWrap);

    const sanitizeStepNumber = (input, maxValue) => {
        if (!input) return;
        input.value = input.value.replace(/[^\d]/g, '');
        const numberValue = input.value === '' ? 0 : Number(input.value);

        if (numberValue < 1) {
            input.value = '1';
            return;
        }

        if (maxValue && numberValue > maxValue) {
            input.value = String(maxValue);
        }
    };

    const syncStepNumbers = () => {
        const rows = Array.from(container.querySelectorAll('.document-step-row'));

        rows.forEach((row, index) => {
            const input = row.querySelector('.step-number-input');
            if (!input) return;

            const maxValue = rows.length;
            const currentValue = Number(input.value || index + 1);
            input.max = String(maxValue);
            input.value = String(Math.min(Math.max(currentValue, 1), maxValue));
        });

        validateStepSequence();
    };

    const createStepRow = () => {
        const row = document.createElement('div');
        row.className = 'row g-3 mt-1 document-step-row align-items-start';
        row.innerHTML = `
            <div class="col-md-1">
                <label class="form-label">Step Number</label>
                <input type="number" class="form-control step-number-input" name="step_number[]" min="1" step="1" inputmode="numeric" pattern="[0-9]*" placeholder="1">
                <div class="mt-2">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-step-btn" title="Remove step">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="col">
                <label class="form-label">Client Step</label>
                <textarea class="form-control" name="client_step[]" rows="3" placeholder="What the client must do"></textarea>
            </div>
            <div class="col">
                <label class="form-label">Agency Action</label>
                <textarea class="form-control" name="agency_action[]" rows="3" placeholder="What the office does"></textarea>
            </div>
            <div class="col-md-1">
                <label class="form-label">Fees</label>
                <textarea class="form-control" name="fees[]" rows="3" placeholder="Fees to be paid or none"></textarea>
            </div>
            <div class="col-md-1">
                <label class="form-label">Time</label>
                <textarea class="form-control" name="processing_time[]" rows="3" placeholder="e.g. 5 Minutes"></textarea>
            </div>
            <div class="col-md-2">
                <label class="form-label">Person Responsible</label>
                <textarea class="form-control" name="person_responsible[]" rows="3" placeholder="Office or person assigned"></textarea>
            </div>
        `;

        const numberInput = row.querySelector('.step-number-input');
        numberInput?.addEventListener('input', function () {
            const maxValue = container.querySelectorAll('.document-step-row').length;
            sanitizeStepNumber(this, maxValue);
            validateStepSequence();
        });

        const removeButton = row.querySelector('.remove-step-btn');
        removeButton?.addEventListener('click', function () {
            row.remove();
            syncStepNumbers();
            recalculateTotals();
        });

        bindStepRowEvents(row);

        return row;
    };

    const initializeDynamicFormSections = () => {
        if (requirementCountInput) {
            requirementCountInput.value = String(getRequirementCount());
        }

        if (transactionTypeCountInput) {
            transactionTypeCountInput.value = String(getTransactionTypeCount());
        }

        container.innerHTML = '';

        if (Array.isArray(window.documentSubmittedSteps) && window.documentSubmittedSteps.length) {
            restoreSubmittedSteps();
        } else {
            const initialStepCount = Math.max(1, Number(window.documentSubmittedStepCount) || 1);
            for (let i = 0; i < initialStepCount; i++) {
                container.appendChild(createStepRow());
            }
        }

        if (requirementCountInput) {
            requirementCountInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        if (transactionTypeCountInput) {
            transactionTypeCountInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        syncStepNumbers();
        recalculateTotals();
    };

    initializeDynamicFormSections();
    validateStepSequence();

    document.querySelectorAll('.step-number-input').forEach((input) => {
        input.addEventListener('input', function () {
            const maxValue = container.querySelectorAll('.document-step-row').length;
            sanitizeStepNumber(this, maxValue);
            validateStepSequence();
        });
    });

    document.querySelector('form')?.addEventListener('submit', function (event) {
        if (!validateStepSequence()) {
            event.preventDefault();
        }
    });

    document.querySelectorAll('.document-step-row').forEach(bindStepRowEvents);

    addButton.addEventListener('click', function () {
        const row = createStepRow();
        const rows = Array.from(container.querySelectorAll('.document-step-row'));

        if (rows.length > 0) {
            const lastStepValue = Number(rows[rows.length - 1].querySelector('.step-number-input')?.value || rows.length);
            const nextNumber = Math.max(1, Number.isFinite(lastStepValue) ? lastStepValue + 1 : rows.length + 1);
            const stepInput = row.querySelector('.step-number-input');
            if (stepInput) {
                stepInput.value = String(nextNumber);
            }
        }

        container.appendChild(row);
        syncStepNumbers();
        recalculateTotals();
    });

    clearButton?.addEventListener('click', function () {
        const form = this.closest('form');
        if (!form) return;

        form.reset();

        if (requirementCountInput) {
            requirementCountInput.value = '1';
            renderRequirementFields(1);
        }

        if (transactionTypeCountInput) {
            transactionTypeCountInput.value = '1';
            renderTransactionTypeFields(1);
        }

        const allRows = Array.from(container.querySelectorAll('.document-step-row'));
        if (allRows.length > 1) {
            allRows.slice(1).forEach(row => row.remove());
        }

        const firstRow = container.querySelector('.document-step-row');
        if (firstRow) {
            const firstInput = firstRow.querySelector('.step-number-input');
            if (firstInput) {
                firstInput.value = '1';
            }
        }

        manualTotalsEnabled = false;
        applyTotalsState();
        syncStepNumbers();
        recalculateTotals();
    });

    applyTotalsState();
    recalculateTotals();
    syncTotalHiddenInputs();
});
