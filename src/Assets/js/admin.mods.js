(() => {
    const syncCustomFieldLabel = (row) => {
        if (!row) {
            return;
        }

        const labelInput = row.querySelector('[name="custom_field_label[]"]');
        const title = row.querySelector('[data-custom-field-title]');
        const triggerButton = row.querySelector('.accordion-button');
        if (!title && !triggerButton) {
            return;
        }

        const labelText = labelInput && labelInput.value ? String(labelInput.value).trim() : 'Field';
        if (title) {
            title.textContent = labelText;
        }

        if (triggerButton) {
            const prefix = triggerButton.querySelector('[data-custom-field-prefix]');
            if (prefix) {
                prefix.textContent = 'Field:';
            }
        }
    };

    const removeFieldRow = (button) => {
        const row = button.closest('.modpress-custom-field-row');
        if (row) {
            row.remove();
        }
    };

    const syncDeleteButtons = () => {
        const buttons = document.querySelectorAll('.btn-delete-selected');
        buttons.forEach((button) => {
            const panel = button.closest('.tab-pane');
            const rows = panel ? panel.querySelectorAll('.modpress-row-checkbox') : document.querySelectorAll('.modpress-row-checkbox');
            const hasSelection = Array.from(rows).some((checkbox) => checkbox.checked);
            button.disabled = !hasSelection;
        });
    };

    const syncModManagerTabs = () => {
        const managerRoot = document.querySelector('.modpress-mod-manager-tab-content');
        if (!managerRoot) {
            return;
        }

        const tabs = managerRoot.querySelectorAll('[data-bs-toggle="tab"]');
        tabs.forEach((tab) => {
            const targetSelector = tab.getAttribute('data-bs-target');
            if (!targetSelector || tab.dataset.modpressTabBound === 'true') {
                return;
            }

            tab.dataset.modpressTabBound = 'true';
            tab.addEventListener('click', () => {
                const pane = managerRoot.querySelector(targetSelector);
                if (!pane) {
                    return;
                }

                managerRoot.querySelectorAll('[data-bs-toggle="tab"]').forEach((link) => {
                    const isActive = link === tab;
                    link.classList.toggle('active', isActive);
                    link.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    if (isActive) {
                        link.setAttribute('aria-current', 'page');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });

                managerRoot.querySelectorAll('.tab-pane').forEach((panel) => {
                    const isActivePane = panel === pane;
                    panel.classList.toggle('show', isActivePane);
                    panel.classList.toggle('active', isActivePane);
                });
            });
        });
    };

    const syncCustomFieldVisibility = (row) => {
        if (!row) {
            return;
        }

        const typeField = row.querySelector('[name="custom_field_type[]"]');
        if (!typeField) {
            return;
        }

        const value = String(typeField.value || '').toLowerCase();
        row.querySelectorAll('[data-custom-field-condition]').forEach((group) => {
            const shouldShow = group.dataset.customFieldCondition === value;
            group.classList.toggle('d-none', !shouldShow);
        });
    };

    const getSelectedValues = (select) => {
        if (!select) {
            return [];
        }

        if (select.multiple) {
            return Array.from(select.selectedOptions || []).map((option) => String(option.value || '')).filter((value) => value !== '');
        }

        const value = String(select.value || '').trim();
        return value !== '' ? [value] : [];
    };

    const setSelectedValues = (select, values = []) => {
        if (!select) {
            return;
        }

        const normalized = Array.isArray(values) ? values : [values];
        const selectedValues = normalized
            .map((value) => String(value ?? ''))
            .filter((value) => value !== '' && value !== '0');

        if (select.multiple) {
            Array.from(select.options || []).forEach((option) => {
                option.selected = selectedValues.includes(String(option.value || ''));
            });
            return;
        }

        select.value = selectedValues[0] || '';
    };

    const syncGroupParentField = (groupKey = '', modal = document.getElementById('modpress-group-modal'), selectedParentEntries = []) => {
        const row = modal ? modal.querySelector('#modpress-group-parent-row') : document.getElementById('modpress-group-parent-row');
        const select = modal ? modal.querySelector('[name="group_parent[]"]') : document.getElementById('group_parent');
        const parentEntryRow = modal ? modal.querySelector('#modpress-group-parent-entry-row') : document.getElementById('modpress-group-parent-entry-row');
        const parentEntrySelect = modal ? modal.querySelector('[name="group_parent_entry[]"]') : document.getElementById('group_parent_entry');
        if (!row || !select) {
            return;
        }

        const isReserved = String(groupKey || '').trim() === 'modpress_type';
        row.classList.toggle('d-none', isReserved);
        if (isReserved) {
            setSelectedValues(select, []);
            if (parentEntrySelect) {
                setSelectedValues(parentEntrySelect, []);
            }
        }

        const parentValues = getSelectedValues(select);
        if (parentEntryRow && parentEntrySelect) {
            const hasParent = parentValues.length > 0 && !isReserved;
            parentEntryRow.classList.toggle('d-none', !hasParent);
        }

        if (parentEntrySelect) {
            const defaultParentEntries = Array.isArray(selectedParentEntries)
                ? selectedParentEntries
                : (selectedParentEntries ? [String(selectedParentEntries)] : getSelectedValues(parentEntrySelect));
            const entries = getGroupParentEntries(parentValues[0] || '', modal);
            populateParentEntryOptions(entries, defaultParentEntries, modal);
            refreshBootstrapSelect(parentEntrySelect);
        }

        if (window.Selectpicker && typeof window.Selectpicker.getOrCreateInstance === 'function') {
            const instance = window.Selectpicker.getOrCreateInstance(select);
            if (instance) {
                instance.refresh();
            }
        }
    };

    const getGroupParentEntries = (parentKey = '', modal = document.getElementById('modpress-group-modal')) => {
        if (!modal) {
            return {};
        }

        try {
            const groups = JSON.parse(modal.dataset.groupEntries || '{}');
            if (parentKey && groups[parentKey]) {
                return groups[parentKey];
            }
        } catch (error) {
            return {};
        }

        return {};
    };

    const refreshBootstrapSelect = (select) => {
        if (!select || typeof window === 'undefined') {
            return;
        }

        if (window.Selectpicker && typeof window.Selectpicker.getOrCreateInstance === 'function') {
            const instance = window.Selectpicker.getOrCreateInstance(select);
            if (instance) {
                instance.refresh();
                return;
            }

            try {
                new window.Selectpicker(select);
            } catch (error) {
                // Ignore initialization errors for non-selectpicker elements.
            }
        }
    };

    const getModpressAjaxConfig = () => {
        const config = window.modpressManager || {};
        const ajaxUrl = config.ajaxUrl || (typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '/wp-admin/admin-ajax.php');

        return {
            ajaxUrl,
            getGroupNonce: config.getGroupNonce || config.nonce || '',
            getGroupEntryNonce: config.getGroupEntryNonce || config.nonce || '',
        };
    };

    const loadGroupEntriesPage = async (link) => {
        if (!link) {
            return false;
        }

        const taxonomyKey = link.dataset.modpressTaxonomyKey || '';
        const pane = taxonomyKey ? document.getElementById('modpress-taxonomy-pane-' + taxonomyKey) : null;
        if (!pane || !link.href) {
            return false;
        }

        try {
            const nextUrl = new URL(link.href, window.location.href);
            const response = await fetch(nextUrl.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return false;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const documentFragment = parser.parseFromString(html, 'text/html');
            const nextPane = documentFragment.getElementById('modpress-taxonomy-pane-' + taxonomyKey);
            if (!nextPane) {
                return false;
            }

            pane.innerHTML = nextPane.innerHTML;
            syncDeleteButtons();
            return true;
        } catch (error) {
            return false;
        }
    };

    const populateEntryModalFields = (modalElement, entryData = {}, taxonomyKey = '') => {
        const data = entryData || {};
        const fieldsToReset = ['group_slug', 'group_entry_id', 'entry_group_taxonomy', 'entry_name', 'entry_slug', 'entry_description', 'entry_parent', 'entry_icon'];
        fieldsToReset.forEach((fieldName) => {
            const input = modalElement.querySelector('[name="' + fieldName + '"]');
            if (input) {
                input.value = '';
            }
        });

        const taxonomyField = modalElement.querySelector('[name="group_slug"]');
        if (taxonomyField) {
            taxonomyField.value = taxonomyKey;
        }
        const formTaxonomyField = modalElement.querySelector('[name="entry_group_taxonomy"]');
        if (formTaxonomyField) {
            formTaxonomyField.value = taxonomyKey;
        }
        const entryIdField = modalElement.querySelector('[name="group_entry_id"]');
        if (entryIdField) {
            entryIdField.value = data.term_id || '';
        }
        const nameField = modalElement.querySelector('[name="entry_name"]');
        if (nameField) {
            nameField.value = data.name || '';
        }
        const slugField = modalElement.querySelector('[name="entry_slug"]');
        if (slugField) {
            slugField.value = data.slug || '';
        }
        const descriptionField = modalElement.querySelector('[name="entry_description"]');
        if (descriptionField) {
            descriptionField.value = data.description || '';
        }
        const parentField = modalElement.querySelector('[name="entry_parent"]');
        if (parentField) {
            parentField.value = data.parent ? String(data.parent) : '0';
        }
        const iconField = modalElement.querySelector('[name="entry_icon"]');
        if (iconField) {
            iconField.value = data.icon || '';
        }

    };

    const fetchEntryModalData = async (trigger) => {
        const datasetEntryId = trigger ? (trigger.dataset.groupEntryId || trigger.dataset.entryId || '') : '';
        if (!trigger || !datasetEntryId) {
            return null;
        }

        const taxonomyKey = trigger.dataset.taxonomyKey || '';
        const entryId = Number(datasetEntryId);
        if (!taxonomyKey || !entryId) {
            return null;
        }

        const { ajaxUrl, getGroupEntryNonce } = getModpressAjaxConfig();
        const formData = new URLSearchParams({
            action: 'modpress_get_group_entry',
            group_slug: taxonomyKey,
            group_entry_id: String(entryId),
            nonce: getGroupEntryNonce || '',
        });

        const response = await fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: formData.toString(),
        });

        const payload = await response.json();
        if (!response.ok || !payload || !payload.success || !payload.data) {
            throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'Unable to load this entry.');
        }

        return payload.data;
    };

    const populateParentEntryOptions = (entries, selectedValues = [], modal = document.getElementById('modpress-group-modal')) => {
        const select = modal ? modal.querySelector('[name="group_parent_entry[]"]') : document.getElementById('group_parent_entry');
        if (!select) {
            return;
        }

        const normalizedSelectedValues = Array.isArray(selectedValues)
            ? selectedValues.map((value) => String(value || '')).filter((value) => value !== '')
            : (selectedValues ? [String(selectedValues)] : []);

        const options = Object.entries(entries || {}).map(([value, label]) => ({ value: String(value), label: String(label) }));
        select.innerHTML = '';

        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = 'Select a parent entry';
        select.appendChild(placeholderOption);

        options.forEach((entry) => {
            const option = document.createElement('option');
            option.value = entry.value;
            option.textContent = entry.label;
            option.selected = normalizedSelectedValues.some((selectedValue) => String(selectedValue || '').trim() === String(entry.value).trim());
            select.appendChild(option);
        });

        setSelectedValues(select, normalizedSelectedValues);
        refreshBootstrapSelect(select);
    };

    const getGroupFields = (groupData = {}) => {
        if (Array.isArray(groupData.group_fields)) {
            return groupData.group_fields;
        }

        if (typeof groupData.group_fields === 'string') {
            try {
                const decoded = JSON.parse(groupData.group_fields);
                return Array.isArray(decoded) ? decoded : [];
            } catch (error) {
                return [];
            }
        }

        return [];
    };

    const getCustomFieldTemplate = (container = null, modal = null) => {
        const modalTemplate = modal ? modal.querySelector('#modpress-custom-field-template') : null;
        if (modalTemplate) {
            return modalTemplate;
        }

        if (container) {
            const containerTemplate = container.querySelector('#modpress-custom-field-template');
            if (containerTemplate) {
                return containerTemplate;
            }
        }

        const globalTemplate = document.getElementById('modpress-custom-field-template');
        if (globalTemplate) {
            if (container && !container.contains(globalTemplate)) {
                container.appendChild(globalTemplate);
            }
            return globalTemplate;
        }

        return null;
    };

    const normalizeOptionEntry = (option = null) => {
        if (!option || typeof option !== 'object') {
            return null;
        }

        const label = String(option.label ?? option.name ?? option.text ?? '').trim();
        const value = String(option.value ?? option.option ?? option.id ?? label).trim();
        if (label === '' && value === '') {
            return null;
        }

        return {
            label: label || value,
            value: value || label,
        };
    };

    const normalizeFieldOptions = (options) => {
        if (Array.isArray(options)) {
            return options
                .map((option) => {
                    if (option && typeof option === 'object') {
                        return normalizeOptionEntry(option);
                    }

                    if (typeof option === 'string') {
                        const trimmed = option.trim();
                        return trimmed ? { label: trimmed, value: trimmed } : null;
                    }

                    return null;
                })
                .filter(Boolean);
        }

        if (typeof options === 'string') {
            try {
                const decoded = JSON.parse(options);
                return normalizeFieldOptions(decoded);
            } catch (error) {
                return [];
            }
        }

        if (options && typeof options === 'object') {
            const entries = Array.isArray(options) ? options : Object.values(options);
            return entries
                .map((entry) => {
                    if (entry && typeof entry === 'object') {
                        return normalizeOptionEntry(entry);
                    }

                    if (typeof entry === 'string') {
                        const trimmed = entry.trim();
                        return trimmed ? { label: trimmed, value: trimmed } : null;
                    }

                    return null;
                })
                .filter(Boolean);
        }

        return [];
    };

    const populateCustomFieldRows = (groupData = {}, modal = document.getElementById('modpress-group-modal')) => {
        if (!modal) {
            return;
        }

        const container = modal.querySelector('#modpress-custom-fields-container');
        if (!container) {
            return;
        }

        const template = getCustomFieldTemplate(container, modal);
        if (!template) {
            return;
        }

        const accordion = container.querySelector('#modpress-custom-fields-accordion') || container;
        accordion.innerHTML = '';
        if (!accordion.contains(template)) {
            accordion.appendChild(template);
        }
        const fields = getGroupFields(groupData);

        if (!fields.length) {
            container.innerHTML = '<div class="text-muted small">No custom fields configured for this group.</div>';
            return;
        }

        fields.forEach((field, fieldIndex) => {
            const row = template.content && template.content.firstElementChild ? template.content.firstElementChild.cloneNode(true) : template.firstElementChild.cloneNode(true);
            row.removeAttribute('id');
            row.classList.remove('d-none');
            row.classList.add('modpress-custom-field-row');
            row.dataset.fieldIndex = String(fieldIndex);

            const headingId = 'modpress-custom-field-heading-' + fieldIndex;
            const collapseId = 'modpress-custom-field-content-' + fieldIndex;
            const header = row.querySelector('.accordion-header');
            const trigger = row.querySelector('.accordion-button');
            const collapse = row.querySelector('.accordion-collapse');

            if (header) {
                header.id = headingId;
            }
            if (trigger) {
                trigger.setAttribute('data-bs-target', '#' + collapseId);
                trigger.setAttribute('aria-controls', collapseId);
                trigger.setAttribute('aria-labelledby', headingId);
                trigger.classList.remove('collapsed');
                trigger.setAttribute('aria-expanded', 'false');
            }
            if (collapse) {
                collapse.id = collapseId;
                collapse.setAttribute('aria-labelledby', headingId);
                collapse.setAttribute('data-bs-parent', '#modpress-custom-fields-accordion');
                collapse.classList.remove('show');
                collapse.classList.add('collapse');
            }

            const fieldId = field.id || field.field_id || field.slug || String(fieldIndex + 1);
            const hiddenFieldId = row.querySelector('[name="custom_field_id[]"]');
            if (hiddenFieldId) {
                hiddenFieldId.value = String(fieldId);
            }
            row.dataset.fieldId = String(fieldId);

            const labelInput = row.querySelector('[name="custom_field_label[]"]');
            if (labelInput) {
                labelInput.value = field.name || field.label || '';
            }

            const slugInput = row.querySelector('[name="custom_field_slug[]"]');
            if (slugInput) {
                slugInput.value = field.slug || '';
            }

            const descriptionInput = row.querySelector('[name="custom_field_description[]"]');
            if (descriptionInput) {
                descriptionInput.value = field.description || '';
            }

            const typeInput = row.querySelector('[name="custom_field_type[]"]');
            if (typeInput) {
                typeInput.value = field.type || 'text';
            }

            const textareaRowsInput = row.querySelector('[name="custom_field_type_textarea_rows[]"]');
            if (textareaRowsInput) {
                textareaRowsInput.value = field.rows || 4;
            }

            const showInTableInput = row.querySelector('[name="custom_field_show_in_table[]"]');
            if (showInTableInput) {
                const shouldShowInTable = Boolean(field.show_in_table || field.showInTable);
                showInTableInput.checked = shouldShowInTable;
            }

            const fieldType = String(field.type || 'radio').toLowerCase();
            row.querySelectorAll('.modpress-option-repeater').forEach((optionRepeater) => {
                const optionItems = optionRepeater.querySelector('.modpress-option-repeater-items');
                const optionTemplate = optionRepeater.querySelector('.modpress-option-row-template');
                if (!optionItems || !optionTemplate) {
                    return;
                }

                const repeaterType = String(optionRepeater.dataset.optionType || 'radio').toLowerCase();
                const shouldPopulate = repeaterType === fieldType || (repeaterType !== 'select' && fieldType === 'radio');
                optionItems.innerHTML = '';

                if (!shouldPopulate) {
                    syncOptionRepeaterNames(optionRepeater, fieldIndex);
                    return;
                }

                const options = normalizeFieldOptions(field.options);
                options.forEach((option) => {
                    const optionRow = optionTemplate.cloneNode(true);
                    optionRow.classList.remove('d-none');
                    optionRow.classList.remove('modpress-option-row-template');
                    optionRow.classList.add('modpress-option-row');

                    const labelInputField = optionRow.querySelector('[data-option-role="label"]');
                    const optionInputField = optionRow.querySelector('[data-option-role="value"]');

                    if (labelInputField) {
                        labelInputField.value = option.label || option.value || '';
                    }

                    if (optionInputField) {
                        optionInputField.value = option.value || option.label || '';
                    }

                    optionItems.appendChild(optionRow);
                });

                if (!options.length) {
                    const emptyRow = optionTemplate.cloneNode(true);
                    emptyRow.classList.remove('d-none');
                    emptyRow.classList.remove('modpress-option-row-template');
                    emptyRow.classList.add('modpress-option-row');
                    optionItems.appendChild(emptyRow);
                }

                syncOptionRepeaterNames(optionRepeater, fieldIndex);
            });

            const imageTypeInputs = row.querySelectorAll('[name^="custom_field_type_image_type_"]');
            if (imageTypeInputs.length) {
                const imageTypes = Array.isArray(field.image_types) ? field.image_types : [];
                imageTypeInputs.forEach((input) => {
                    const value = String(input.getAttribute('value') || input.value || '').trim();
                    input.checked = imageTypes.includes(value);
                });
            }

            accordion.appendChild(row);
            syncCustomFieldRepeaterNames(row);
            syncCustomFieldVisibility(row);
        });
    };

    const syncGroupDeleteState = (modal = document.getElementById('modpress-group-modal')) => {
        if (!modal) {
            return;
        }

        const deleteWrap = modal.querySelector('.modpress-group-delete-wrap');
        const deleteButton = modal.querySelector('[data-role="delete-group"]');
        const editKeyInput = modal.querySelector('[name="group_edit_key"]');
        const deleteForm = modal.querySelector('[data-role="group-delete-form"]');
        const deleteGroupIdInput = deleteForm ? deleteForm.querySelector('[name="group_id"]') : null;
        const deleteGroupKeyInput = deleteForm ? deleteForm.querySelector('[name="group_edit_key"]') : null;
        const groupKey = String(editKeyInput ? editKeyInput.value : '').trim();
        const shouldShowDelete = Boolean(groupKey) && groupKey !== 'modpress_type';

        if (deleteWrap) {
            deleteWrap.classList.toggle('d-none', !shouldShowDelete);
        }

        if (deleteButton) {
            deleteButton.disabled = !shouldShowDelete;
        }

        if (deleteForm) {
            if (deleteGroupIdInput) {
                deleteGroupIdInput.value = deleteForm.dataset.groupId || '';
            }
            if (deleteGroupKeyInput) {
                deleteGroupKeyInput.value = groupKey;
            }
        }
    };

    const resetGroupModal = (modal = document.getElementById('modpress-group-modal')) => {
        if (!modal) {
            return;
        }

        const fields = [
            'group_edit_key',
            'group_name',
            'group_singular_name',
            'group_slug',
            'group_description',
            'group_parent[]',
            'group_parent_entry[]',
        ];

        fields.forEach((fieldName) => {
            const input = modal.querySelector('[name="' + fieldName + '"]');
            if (input) {
                if (input.multiple) {
                    setSelectedValues(input, []);
                } else {
                    input.value = '';
                }
            }
        });

        const customFieldsContainer = modal.querySelector('#modpress-custom-fields-container');
        if (customFieldsContainer) {
            const customFieldTemplate = getCustomFieldTemplate(customFieldsContainer, modal);
            customFieldsContainer.innerHTML = '';
            if (customFieldTemplate && !customFieldsContainer.contains(customFieldTemplate)) {
                customFieldsContainer.appendChild(customFieldTemplate);
            }
        }

        const hierarchicalInput = modal.querySelector('[name="group_hierarchical"]');
        if (hierarchicalInput) {
            hierarchicalInput.checked = false;
        }

        const deleteForm = modal.querySelector('[data-role="group-delete-form"]');
        if (deleteForm) {
            const groupIdInput = deleteForm.querySelector('[name="group_id"]');
            const groupKeyInput = deleteForm.querySelector('[name="group_edit_key"]');
            if (groupIdInput) {
                groupIdInput.value = '';
            }
            if (groupKeyInput) {
                groupKeyInput.value = '';
            }
        }

        const modalTitle = modal.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Add Group';
        }

        syncGroupParentField('');
        syncGroupDeleteState();
    };

    const getNextCustomFieldIndex = (scope = document) => {
        const rows = scope && scope.querySelectorAll ? scope.querySelectorAll('.modpress-custom-field-row') : document.querySelectorAll('.modpress-custom-field-row');
        let maxIndex = -1;

        rows.forEach((row) => {
            const current = Number(row.dataset.fieldIndex || 0);
            if (!Number.isNaN(current) && current > maxIndex) {
                maxIndex = current;
            }
        });

        return maxIndex + 1;
    };

    const getNextCustomFieldId = (scope = document) => {
        const rows = scope && scope.querySelectorAll ? scope.querySelectorAll('.modpress-custom-field-row') : document.querySelectorAll('.modpress-custom-field-row');
        let maxId = 0;

        rows.forEach((row) => {
            const hiddenInput = row.querySelector('[name="custom_field_id[]"]');
            const candidate = hiddenInput ? String(hiddenInput.value || row.dataset.fieldId || '') : String(row.dataset.fieldId || '');
            const numericId = Number(String(candidate).replace(/\D/g, ''));
            if (!Number.isNaN(numericId) && numericId > maxId) {
                maxId = numericId;
            }
        });

        return String(maxId + 1);
    };

    const syncOptionRepeaterNames = (repeater, fieldIndex) => {
        if (!repeater || fieldIndex === undefined || fieldIndex === null || Number.isNaN(Number(fieldIndex))) {
            return;
        }

        const type = repeater.dataset.optionType || 'radio';
        const optionRows = Array.from(repeater.querySelectorAll('.modpress-option-row')).filter((optionRow) => !optionRow.closest('.modpress-option-row-template'));
        optionRows.forEach((optionRow, optionIndex) => {
            const labelInput = optionRow.querySelector('[data-option-role="label"]');
            const valueInput = optionRow.querySelector('[data-option-role="value"]');
            if (labelInput) {
                labelInput.name = 'custom_field_type_' + type + '[' + fieldIndex + '][' + optionIndex + '][label]';
            }
            if (valueInput) {
                valueInput.name = 'custom_field_type_' + type + '[' + fieldIndex + '][' + optionIndex + '][option]';
            }
        });
    };

    const syncCustomFieldRepeaterNames = (row) => {
        if (!row) {
            return;
        }

        const fieldIndex = row.dataset.fieldIndex;
        if (!fieldIndex && fieldIndex !== '0') {
            return;
        }

        row.querySelectorAll('.modpress-option-repeater').forEach((repeater) => {
            syncOptionRepeaterNames(repeater, fieldIndex);
        });
    };

    const fetchGroupModalData = async (trigger) => {
        if (!trigger) {
            return null;
        }

        const groupId = trigger.dataset.groupId || '';
        const groupKey = trigger.dataset.groupKey || '';
        if (!groupId && !groupKey) {
            return null;
        }

        const { ajaxUrl, getGroupNonce } = getModpressAjaxConfig();
        const formData = new URLSearchParams({
            action: 'modpress_get_group',
            group_id: String(groupId || ''),
            group_key: String(groupKey || ''),
            nonce: getGroupNonce || '',
        });

        const response = await fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: formData.toString(),
        });

        const payload = await response.json();
        if (!response.ok || !payload || !payload.success || !payload.data) {
            throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'Unable to load this group.');
        }

        return payload.data;
    };

    const populateGroupModalFields = (modalElement, groupData = {}) => {
        if (!modalElement) {
            return;
        }

        const groupKey = String(groupData.group_key || groupData.group_slug || groupData.group_id || '').trim();
        const groupNameInput = modalElement.querySelector('[name="group_name"]');
        const groupSingularInput = modalElement.querySelector('[name="group_singular_name"]');
        const groupSlugInput = modalElement.querySelector('[name="group_slug"]');
        const groupDescriptionInput = modalElement.querySelector('[name="group_description"]');
        const groupHierarchicalInput = modalElement.querySelector('[name="group_hierarchical"]');
        const groupParentInput = modalElement.querySelector('[name="group_parent[]"]');
        const groupParentEntryInput = modalElement.querySelector('[name="group_parent_entry[]"]');
        const groupEditKeyInput = modalElement.querySelector('[name="group_edit_key"]');
        const groupIdInput = modalElement.querySelector('[name="group_id"]');

        if (groupEditKeyInput) {
            groupEditKeyInput.value = groupKey;
        }
        if (groupIdInput) {
            groupIdInput.value = String(groupData.group_id || 0);
        }
        if (groupNameInput) {
            groupNameInput.value = groupData.group_name || '';
        }
        if (groupSingularInput) {
            groupSingularInput.value = groupData.group_singular_name || '';
        }
        if (groupSlugInput) {
            groupSlugInput.value = groupData.group_slug || '';
        }
        if (groupDescriptionInput) {
            groupDescriptionInput.value = groupData.group_description || '';
        }
        if (groupHierarchicalInput) {
            groupHierarchicalInput.checked = Boolean(groupData.group_hierarchical);
        }
        if (groupParentInput) {
            const parentValues = Array.isArray(groupData.group_parent) ? groupData.group_parent : (groupData.group_parent ? [groupData.group_parent] : []);
            setSelectedValues(groupParentInput, parentValues.map((value) => String(value || '')));
            refreshBootstrapSelect(groupParentInput);
        }

        const selectedParentEntryValues = Array.isArray(groupData.group_parent_entry) ? groupData.group_parent_entry : (groupData.group_parent_entry ? [groupData.group_parent_entry] : []);
        if (groupParentEntryInput) {
            setSelectedValues(groupParentEntryInput, selectedParentEntryValues.map((value) => String(value || '')));
            refreshBootstrapSelect(groupParentEntryInput);
        }

        const modalTitle = modalElement.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = groupKey ? 'Edit Group' : 'Add Group';
        }

        populateCustomFieldRows(groupData, modalElement);
        syncGroupParentField(groupKey, modalElement, selectedParentEntryValues);
        syncGroupDeleteState(modalElement);
    };

    const loadGroupModalData = (trigger, modalElement = null) => {
        const targetModal = modalElement || (trigger && trigger.dataset && trigger.dataset.bsTarget ? document.querySelector(trigger.dataset.bsTarget) : document.getElementById('modpress-group-modal'));
        if (!targetModal) {
            return;
        }

        const groupId = trigger ? (trigger.dataset.groupId || '') : '';
        const groupKey = trigger ? (trigger.dataset.groupKey || '') : '';
        if (!groupId && !groupKey) {
            resetGroupModal(targetModal);
            return;
        }

        fetchGroupModalData(trigger)
            .then((groupData) => {
                if (!groupData) {
                    return;
                }
                populateGroupModalFields(targetModal, groupData);
            })
            .catch((error) => {
                console.warn('[ModPress] Group data fetch failed:', error);
                resetGroupModal(targetModal);
            });
    };

    const createOptionRow = (repeater, fieldIndex, optionIndex = null) => {
        if (!repeater) {
            return null;
        }

        const template = repeater.querySelector('.modpress-option-row-template');
        if (!template) {
            return null;
        }

        const items = repeater.querySelector('.modpress-option-repeater-items');
        if (!items) {
            return null;
        }

        const actualRows = Array.from(repeater.querySelectorAll('.modpress-option-row')).filter((row) => !row.closest('.modpress-option-row-template'));
        const resolvedIndex = optionIndex !== null && optionIndex !== undefined ? optionIndex : actualRows.length;

        const clone = template.cloneNode(true);
        clone.classList.remove('d-none');
        clone.classList.remove('modpress-option-row-template');
        clone.classList.add('modpress-option-row');

        if (fieldIndex !== null && fieldIndex !== undefined && fieldIndex !== '') {
            const labelInput = clone.querySelector('[data-option-role="label"]');
            const valueInput = clone.querySelector('[data-option-role="value"]');
            const type = repeater.dataset.optionType || 'radio';
            if (labelInput) {
                labelInput.name = 'custom_field_type_' + type + '[' + fieldIndex + '][' + resolvedIndex + '][label]';
            }
            if (valueInput) {
                valueInput.name = 'custom_field_type_' + type + '[' + fieldIndex + '][' + resolvedIndex + '][option]';
            }
        }

        items.appendChild(clone);
        return clone;
    };

    const syncOptionRepeaterType = (row) => {
        if (!row) {
            return;
        }

        const typeField = row.querySelector('[name="custom_field_type[]"]');
        const repeaters = row.querySelectorAll('.modpress-option-repeater');
        if (!typeField || !repeaters.length) {
            return;
        }

        const nextType = String(typeField.value || 'radio').toLowerCase();
        repeaters.forEach((repeater) => {
            const repeaterType = String(repeater.dataset.optionType || 'radio').toLowerCase();
            const shouldShow = repeaterType === nextType;
            repeater.classList.toggle('d-none', !shouldShow);
            const repeatableGroup = repeater.closest('tr');
            if (repeatableGroup) {
                repeatableGroup.classList.toggle('d-none', !shouldShow);
            }
            syncOptionRepeaterNames(repeater, row.dataset.fieldIndex);
        });
    };

    const addCustomFieldRow = (button = null) => {
        const scope = button ? button.closest('.modal') : null;
        const container = scope ? scope.querySelector('.modpress-custom-fields-container') : document.getElementById('modpress-custom-fields-container');
        if (!container) {
            console.error('[ModPress] Missing custom fields container for Add Custom Field action.');
            return;
        }

        const accordion = container.querySelector('#modpress-custom-fields-accordion') || container;
        const placeholder = container.querySelector('.text-muted.small');
        if (placeholder) {
            placeholder.remove();
        }

        const template = getCustomFieldTemplate(container, scope);
        if (!template) {
            console.error('[ModPress] Missing custom field template for Add Custom Field action.');
            return;
        }

        let row = null;
        try {
            if (template.content && template.content.firstElementChild) {
                row = template.content.firstElementChild.cloneNode(true);
            } else if (template.firstElementChild) {
                row = template.firstElementChild.cloneNode(true);
            }
        } catch (error) {
            console.error('[ModPress] Failed to clone custom field template.', error);
            return;
        }

        if (!row) {
            console.error('[ModPress] Custom field template does not contain a valid row to clone.');
            return;
        }

        const fieldIndex = getNextCustomFieldIndex(container);
        const fieldId = getNextCustomFieldId(container);
        row.classList.remove('d-none');
        row.classList.add('modpress-custom-field-row');
        row.dataset.fieldIndex = String(fieldIndex);
        row.dataset.fieldId = fieldId;

        const hiddenFieldId = row.querySelector('[name="custom_field_id[]"]');
        if (hiddenFieldId) {
            hiddenFieldId.value = fieldId;
        }

        const headingId = 'modpress-custom-field-heading-' + fieldIndex;
        const collapseId = 'modpress-custom-field-content-' + fieldIndex;
        const header = row.querySelector('.accordion-header');
        const trigger = row.querySelector('.accordion-button');
        const collapse = row.querySelector('.accordion-collapse');

        if (header) {
            header.id = headingId;
        }
        if (trigger) {
            trigger.setAttribute('data-bs-target', '#' + collapseId);
            trigger.setAttribute('aria-controls', collapseId);
            trigger.setAttribute('aria-labelledby', headingId);
            trigger.querySelector('[data-custom-field-title]')?.setAttribute('data-custom-field-title', '');
        }
        if (collapse) {
            collapse.id = collapseId;
            collapse.setAttribute('aria-labelledby', headingId);
            collapse.setAttribute('data-bs-parent', '#modpress-custom-fields-accordion');
        }

        const repeater = row.querySelector('.modpress-option-repeater');
        if (repeater) {
            repeater.dataset.optionType = 'radio';
            createOptionRow(repeater, fieldIndex, 0);
        }

        accordion.appendChild(row);
        syncCustomFieldRepeaterNames(row);
        syncCustomFieldVisibility(row);
        syncCustomFieldLabel(row);
        syncOptionRepeaterType(row);
    };

    const bindCustomFieldButtons = () => {
        document.querySelectorAll('[data-role="add-custom-field"]').forEach((button) => {
            if (button.dataset.modpressCustomFieldBound === 'true') {
                return;
            }

            button.dataset.modpressCustomFieldBound = 'true';
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                try {
                    addCustomFieldRow(button);
                } catch (error) {
                    console.error('[ModPress] Failed to add custom field row.', error);
                }
            });
        });
    };

    const addOptionRow = (button) => {
        if (!button || button.dataset.modpressOptionAddPending === 'true') {
            return;
        }

        const repeater = button.closest('.modpress-option-repeater');
        if (!repeater) {
            return;
        }

        button.dataset.modpressOptionAddPending = 'true';

        try {
            const fieldRow = button.closest('.modpress-custom-field-row');
            const fieldIndex = fieldRow ? fieldRow.dataset.fieldIndex : null;
            const optionIndex = Array.from(repeater.querySelectorAll('.modpress-option-row')).filter((row) => !row.closest('.modpress-option-row-template')).length;
            const clone = createOptionRow(repeater, fieldIndex, optionIndex);
            if (clone) {
                syncOptionRepeaterNames(repeater, fieldIndex);
            }
        } finally {
            window.setTimeout(() => {
                delete button.dataset.modpressOptionAddPending;
            }, 0);
        }
    };

    document.addEventListener('change', (event) => {
        const target = event.target;
        if (target.matches('.modpress-row-checkbox')) {
            syncDeleteButtons();
        }

        if (target.matches('.modpress-select-all')) {
            const taxonomy = target.dataset.taxonomy;
            const rows = document.querySelectorAll('.modpress-row-checkbox[data-taxonomy="' + taxonomy + '"]');
            rows.forEach((checkbox) => {
                checkbox.checked = target.checked;
            }
            );
            syncDeleteButtons();
        }

        if (target.matches('[name="custom_field_label[]"]')) {
            syncCustomFieldLabel(target.closest('.modpress-custom-field-row'));
        }

        if (target.matches('[name="custom_field_type[]"]')) {
            const fieldRow = target.closest('.modpress-custom-field-row');
            syncCustomFieldVisibility(fieldRow);
            syncOptionRepeaterType(fieldRow);
        }

        if (target.matches('[name="group_parent[]"]')) {
            const modal = target.closest('.modal');
            const parentValues = getSelectedValues(target);
            const parentValue = parentValues[0] || '';
            const parentEntryRow = modal ? modal.querySelector('#modpress-group-parent-entry-row') : document.getElementById('modpress-group-parent-entry-row');
            const parentEntrySelect = modal ? modal.querySelector('[name="group_parent_entry[]"]') : document.getElementById('group_parent_entry');
            if (parentEntryRow && parentEntrySelect) {
                parentEntryRow.classList.toggle('d-none', '' === parentValue);
            }
            populateParentEntryOptions(getGroupParentEntries(parentValue, modal), parentEntrySelect ? getSelectedValues(parentEntrySelect) : [], modal);
            if (parentEntrySelect) {
                refreshBootstrapSelect(parentEntrySelect);
            }
        }

        if (target.matches('[name$="_mode"]') || target.matches('.modpress-image-picker-mode-input')) {
            const fieldWrapper = target.closest('.modpress-image-field');
            if (!fieldWrapper) {
                return;
            }

            const mode = target.value;
            const imageMode = fieldWrapper.querySelector('.modpress-image-entry-mode-image');
            const iconMode = fieldWrapper.querySelector('.modpress-image-entry-mode-icon');
            const urlMode = fieldWrapper.querySelector('.modpress-image-entry-mode-url');
            const activeHidden = fieldWrapper.querySelector('input[type="hidden"][name="' + (target.dataset.inputName || target.name.replace(/_mode$/, '')) + '"]');
            const imageHidden = fieldWrapper.querySelector('.modpress-image-entry-mode-image input[type="hidden"]');
            const iconHidden = fieldWrapper.querySelector('.modpress-fa-picker-container input[type="hidden"]');
            const urlInput = fieldWrapper.querySelector('.modpress-image-entry-mode-url input[type="url"]');

            if (imageMode) {
                imageMode.style.display = mode === 'image' ? '' : 'none';
            }
            if (iconMode) {
                iconMode.style.display = mode === 'icon' ? '' : 'none';
            }
            if (urlMode) {
                urlMode.style.display = mode === 'url' ? '' : 'none';
            }

            if (imageHidden) {
                imageHidden.disabled = mode !== 'image';
            }
            if (iconHidden) {
                iconHidden.disabled = mode !== 'icon';
            }
            if (urlInput) {
                urlInput.disabled = mode !== 'url';
            }

            if (mode === 'icon' && iconHidden && (!iconHidden.value || !String(iconHidden.value).includes('fa-'))) {
                iconHidden.value = 'fas fa-image';
            }

            if (activeHidden && mode === 'image') {
                activeHidden.value = activeHidden.value || '';
            }
        }

    });

    document.addEventListener('click', (event) => {
        const groupPageLink = event.target.closest('.modpress-group-page-link');
        if (groupPageLink) {
            event.preventDefault();
            loadGroupEntriesPage(groupPageLink);
            return;
        }

        const deleteRowButton = event.target.closest('[data-role="delete-group-entry"]');
        if (deleteRowButton) {
            event.preventDefault();
            const form = deleteRowButton.closest('form');
            if (!form) {
                return;
            }
            if (!window.confirm('Delete this entry?')) {
                return;
            }
            form.submit();
            return;
        }

        const bulkDeleteButton = event.target.closest('.btn-delete-selected');
        if (bulkDeleteButton) {
            event.preventDefault();
            const form = bulkDeleteButton.closest('form');
            if (!form) {
                return;
            }
            const checkedRows = form.querySelectorAll('.modpress-row-checkbox:checked');
            if (!checkedRows.length) {
                return;
            }
            if (!window.confirm('Delete the selected entries?')) {
                return;
            }
            form.submit();
            return;
        }

        const button = event.target.closest('[data-role="add-custom-field"]');
        if (button) {
            event.preventDefault();
            try {
                addCustomFieldRow(button);
            } catch (error) {
                console.error('[ModPress] Failed to add custom field row.', error);
            }
            return;
        }

        const optionButton = event.target.closest('[data-role="add-option-row"]');
        if (optionButton) {
            event.preventDefault();
            event.stopPropagation();
            addOptionRow(optionButton);
            return;
        }

        const removeOptionButton = event.target.closest('[data-role="remove-option-row"]');
        if (removeOptionButton) {
            event.preventDefault();
            event.stopPropagation();
            const optionRow = removeOptionButton.closest('.modpress-option-row');
            if (optionRow) {
                optionRow.remove();
            }
            return;
        }

        const deleteGroupButton = event.target.closest('[data-role="delete-group"]');
        if (deleteGroupButton) {
            event.preventDefault();
            const form = deleteGroupButton.form || deleteGroupButton.closest('form');
            if (!form) {
                return;
            }
            const groupKey = form.querySelector('[name="group_edit_key"]')?.value || '';
            if (groupKey && groupKey !== 'modpress_type') {
                if (!window.confirm('Delete this group?')) {
                    return;
                }
                form.submit();
            }
            return;
        }

        const removeButton = event.target.closest('[data-role="remove-custom-field"]');
        if (removeButton) {
            removeFieldRow(removeButton);
        }

        const groupEditButton = event.target.closest('.modpress-tab-edit-btn');
        if (groupEditButton) {
            return;
        }
    });

    const loadEntryModalData = (trigger) => {
        if (!trigger) {
            return;
        }

        const taxonomyKey = trigger.dataset.taxonomyKey || '';
        const fallbackTarget = taxonomyKey ? '#modpress-entry-modal-' + taxonomyKey.replace(/[^a-zA-Z0-9_-]/g, '-') : '#modpress-entry-modal';
        const targetSelector = trigger.dataset.bsTarget || fallbackTarget;
        const modalElement = document.querySelector(targetSelector);
        if (!modalElement) {
            return;
        }

        const taxonomyLabel = trigger.dataset.taxonomyLabel || 'Group';
        const title = modalElement.querySelector('.modal-title');
        const entryIdValue = trigger.dataset.groupEntryId || trigger.dataset.entryId || '';
        if (title) {
            const isEdit = Boolean(entryIdValue);
            title.textContent = isEdit ? 'Edit Entry - ' + taxonomyLabel : 'Add New Entry - ' + taxonomyLabel;
        }

        const fieldsToReset = ['group_slug', 'group_entry_id', 'entry_group_taxonomy', 'entry_name', 'entry_slug', 'entry_description', 'entry_parent', 'entry_icon'];
        fieldsToReset.forEach((fieldName) => {
            const input = modalElement.querySelector('[name="' + fieldName + '"]');
            if (input) {
                input.value = '';
            }
        });

        const taxonomyField = modalElement.querySelector('[name="group_slug"]');
        if (taxonomyField) {
            taxonomyField.value = taxonomyKey;
        }
        const formTaxonomyField = modalElement.querySelector('[name="entry_group_taxonomy"]');
        if (formTaxonomyField) {
            formTaxonomyField.value = taxonomyKey;
        }

        const entryId = trigger.dataset.groupEntryId ? Number(trigger.dataset.groupEntryId) : (trigger.dataset.entryId ? Number(trigger.dataset.entryId) : 0);
        if (!entryId) {
            return;
        }

        const entryIdField = modalElement.querySelector('[name="group_entry_id"]');
        if (entryIdField) {
            entryIdField.value = String(entryId);
        }

        fetchEntryModalData(trigger)
            .then((entryData) => {
                if (!entryData) {
                    return;
                }
                populateEntryModalFields(modalElement, entryData, taxonomyKey);
            })
            .catch((error) => {
                console.warn('[ModPress] Entry data fetch failed:', error);
            });
    };

    document.querySelectorAll('[id^="modpress-group-modal"]').forEach((modalElement) => {
        modalElement.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) {
                resetGroupModal(modalElement);
                return;
            }
            loadGroupModalData(trigger, modalElement);
        });
    });

    document.querySelectorAll('[id^="modpress-entry-modal-"]').forEach((modalElement) => {
        modalElement.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }
            loadEntryModalData(trigger);
        });
    });

    bindCustomFieldButtons();
    syncDeleteButtons();
    syncModManagerTabs();
})();