(() => {
    const removeFieldRow = (button) => {
        const row = button.closest('.modpress-custom-field-row');
        if (row && document.querySelectorAll('.modpress-custom-field-row').length > 1) {
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

        const customFieldValues = data.custom_fields && typeof data.custom_fields === 'object' ? data.custom_fields : {};
        renderEntryCustomFields(modalElement, customFieldValues);
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

        container.innerHTML = '';
        if (!container.contains(template)) {
            container.appendChild(template);
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

            const optionRepeater = row.querySelector('.modpress-option-repeater');
            if (optionRepeater) {
                const optionItems = optionRepeater.querySelector('.modpress-option-repeater-items');
                const optionTemplate = optionRepeater.querySelector('.modpress-option-row-template');
                if (optionItems && optionTemplate) {
                    optionItems.innerHTML = '';
                    const options = Array.isArray(field.options) ? field.options : [];
                    const fieldType = String(field.type || 'radio').toLowerCase();
                    optionRepeater.dataset.optionType = fieldType === 'select' ? 'select' : 'radio';

                    options.forEach((option, optionIndex) => {
                        const optionRow = optionTemplate.cloneNode(true);
                        optionRow.classList.remove('d-none');
                        optionRow.classList.remove('modpress-option-row-template');
                        optionRow.classList.add('modpress-option-row');

                        const labelInputField = optionRow.querySelector('[data-option-role="label"]');
                        const optionInputField = optionRow.querySelector('[data-option-role="value"]');

                        if (labelInputField) {
                            labelInputField.value = option.label || option.name || '';
                        }

                        if (optionInputField) {
                            optionInputField.value = option.value || option.option || option.label || '';
                        }

                        syncOptionRepeaterNames(optionRepeater, fieldIndex);
                        optionItems.appendChild(optionRow);
                        syncOptionRepeaterNames(optionRepeater, fieldIndex);
                    });

                    if (!options.length) {
                        const emptyRow = optionTemplate.cloneNode(true);
                        emptyRow.classList.remove('d-none');
                        emptyRow.classList.remove('modpress-option-row-template');
                        emptyRow.classList.add('modpress-option-row');
                        optionItems.appendChild(emptyRow);
                    }

                    syncOptionRepeaterNames(optionRepeater, fieldIndex);
                }
            }

            const imageTypeInputs = row.querySelectorAll('[name^="custom_field_type_image_type_"]');
            if (imageTypeInputs.length) {
                const imageTypes = Array.isArray(field.image_types) ? field.image_types : [];
                imageTypeInputs.forEach((input) => {
                    const value = String(input.getAttribute('value') || input.value || '').trim();
                    input.checked = imageTypes.includes(value);
                });
            }

            container.appendChild(row);
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

    const syncOptionRepeaterNames = (repeater, fieldIndex) => {
        if (!repeater || fieldIndex === undefined || fieldIndex === null || Number.isNaN(Number(fieldIndex))) {
            return;
        }

        const type = repeater.dataset.optionType || 'radio';
        const optionRows = repeater.querySelectorAll('.modpress-option-row');
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

    const addCustomFieldRow = (button = null) => {
        const scope = button ? button.closest('.modal') : null;
        const container = scope ? scope.querySelector('.modpress-custom-fields-container') : document.getElementById('modpress-custom-fields-container');
        if (!container) {
            console.error('[ModPress] Missing custom fields container for Add Custom Field action.');
            return;
        }

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

        row.classList.remove('d-none');
        row.classList.add('modpress-custom-field-row');
        row.dataset.fieldIndex = String(getNextCustomFieldIndex(container));

        const repeater = row.querySelector('.modpress-option-repeater');
        if (repeater) {
            repeater.dataset.optionType = 'radio';
            const templateRow = repeater.querySelector('.modpress-option-row-template');
            if (templateRow) {
                templateRow.classList.remove('d-none');
                templateRow.classList.add('modpress-option-row');
                templateRow.querySelectorAll('[data-option-role]').forEach((input) => {
                    input.name = '';
                });
            }
        }

        container.appendChild(row);
        syncCustomFieldRepeaterNames(row);
        syncCustomFieldVisibility(row);
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
        const repeater = button.closest('.modpress-option-repeater');
        if (!repeater) {
            return;
        }

        const fieldRow = button.closest('.modpress-custom-field-row');
        const fieldIndex = fieldRow ? fieldRow.dataset.fieldIndex : null;
        const template = repeater.querySelector('.modpress-option-row-template');
        if (!template) {
            return;
        }

        const clone = template.cloneNode(true);
        clone.classList.remove('d-none');
        clone.classList.remove('modpress-option-row-template');
        clone.classList.add('modpress-option-row');

        if (fieldIndex !== null && fieldIndex !== undefined && fieldIndex !== '') {
            const optionIndex = repeater.querySelectorAll('.modpress-option-row').length;
            const labelInput = clone.querySelector('[data-option-role="label"]');
            const valueInput = clone.querySelector('[data-option-role="value"]');
            const type = repeater.dataset.optionType || 'radio';
            if (labelInput) {
                labelInput.name = 'custom_field_type_' + type + '[' + fieldIndex + '][' + optionIndex + '][label]';
            }
            if (valueInput) {
                valueInput.name = 'custom_field_type_' + type + '[' + fieldIndex + '][' + optionIndex + '][option]';
            }
        }

        const items = repeater.querySelector('.modpress-option-repeater-items');
        if (items) {
            items.appendChild(clone);
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

        if (target.matches('[name="custom_field_type[]"]')) {
            syncCustomFieldVisibility(target.closest('.modpress-custom-field-row'));
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

        if (target.matches('.modpress-image-picker-mode-input')) {
            const fieldWrapper = target.closest('.modpress-image-field');
            if (!fieldWrapper) {
                return;
            }

            const mode = target.value;
            const imageMode = fieldWrapper.querySelector('.modpress-image-entry-mode-image');
            const iconMode = fieldWrapper.querySelector('.modpress-image-entry-mode-icon');
            const activeHidden = fieldWrapper.querySelector('input[type="hidden"][name="' + target.dataset.inputName + '"]');
            const imageHidden = fieldWrapper.querySelector('.modpress-image-entry-mode-image input[type="hidden"]');
            const iconHidden = fieldWrapper.querySelector('.modpress-fa-picker-container input[type="hidden"]');

            if (imageMode) {
                imageMode.style.display = mode === 'image' ? '' : 'none';
            }
            if (iconMode) {
                iconMode.style.display = mode === 'icon' ? '' : 'none';
            }

            if (imageHidden) {
                imageHidden.disabled = mode !== 'image';
            }
            if (iconHidden) {
                iconHidden.disabled = mode !== 'icon';
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
        const imageSelectButton = event.target.closest('.modpress-image-select');
        if (imageSelectButton) {
            const fieldWrapper = imageSelectButton.closest('.modpress-image-field');
            if (!fieldWrapper || typeof window === 'undefined' || !window.wp || !window.wp.media) {
                return;
            }

            const hiddenInput = fieldWrapper.querySelector('input[type="hidden"]');
            const preview = fieldWrapper.querySelector('.modpress-image-preview');
            const previewImg = fieldWrapper.querySelector('.modpress-image-preview img');
            const removeButton = fieldWrapper.querySelector('.modpress-image-clear');
            const mediaFrame = window.wp.media({
                title: 'Select Image',
                button: { text: 'Use Image' },
                multiple: false,
                library: { type: 'image' },
            });

            mediaFrame.on('select', () => {
                const attachment = mediaFrame.state().get('selection').first().toJSON();
                const imageUrl = attachment.url || attachment.sizes?.full?.url || attachment.icon || '';

                if (hiddenInput) {
                    hiddenInput.value = imageUrl;
                }

                if (preview && previewImg) {
                    previewImg.src = imageUrl;
                    preview.classList.toggle('d-none', !imageUrl);
                }

                if (removeButton) {
                    removeButton.classList.toggle('d-none', !imageUrl);
                }
            });

            mediaFrame.open();
            return;
        }

        const imageClearButton = event.target.closest('.modpress-image-clear');
        if (imageClearButton) {
            const fieldWrapper = imageClearButton.closest('.modpress-image-field');
            if (!fieldWrapper) {
                return;
            }

            const hiddenInput = fieldWrapper.querySelector('input[type="hidden"]');
            const preview = fieldWrapper.querySelector('.modpress-image-preview');
            const previewImg = fieldWrapper.querySelector('.modpress-image-preview img');
            if (hiddenInput) {
                hiddenInput.value = '';
            }
            if (preview && previewImg) {
                previewImg.src = '';
                preview.classList.add('d-none');
            }
            imageClearButton.classList.add('d-none');
            return;
        }

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
            addOptionRow(optionButton);
            return;
        }

        const removeOptionButton = event.target.closest('[data-role="remove-option-row"]');
        if (removeOptionButton) {
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

    const escapeAttributeValue = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const buildEntryImageSelector = (inputName, value, mode = 'image') => {
        const template = document.getElementById('modpress-entry-image-field-template');
        if (!template) {
            return '';
        }

        const fragment = template.content ? template.content.cloneNode(true) : template.cloneNode(true);
        const field = fragment.firstElementChild || fragment;
        if (!field) {
            return '';
        }

        const sanitizedValue = String(value || '');
        const imageValue = sanitizedValue && mode !== 'icon' ? sanitizedValue : '';
        const iconValue = mode === 'icon' && sanitizedValue ? sanitizedValue : 'fas fa-image';
        const pickerId = 'modpress-entry-icon-picker-' + inputName.replace(/[^a-zA-Z0-9_-]/g, '-');

        field.dataset.inputName = inputName;
        field.querySelectorAll('[name=""]').forEach((element) => {
            element.removeAttribute('name');
        });

        const modeInputs = field.querySelectorAll('.modpress-image-picker-mode-input');
        modeInputs.forEach((radio) => {
            radio.name = inputName + '_mode';
            radio.dataset.inputName = inputName;
            radio.checked = radio.value === mode;
        });

        const imageHidden = field.querySelector('.modpress-image-entry-mode-image input[type="hidden"]');
        if (imageHidden) {
            imageHidden.name = inputName;
            imageHidden.value = imageValue;
            imageHidden.disabled = mode !== 'image';
        }

        const imagePreview = field.querySelector('.modpress-image-preview');
        const imagePreviewImg = field.querySelector('.modpress-image-preview img');
        if (imagePreview && imagePreviewImg) {
            imagePreviewImg.src = imageValue;
            imagePreview.classList.toggle('d-none', !imageValue);
        }

        const imageClear = field.querySelector('.modpress-image-clear');
        if (imageClear) {
            imageClear.classList.toggle('d-none', !imageValue);
        }

        const iconHidden = field.querySelector('.modpress-image-entry-mode-icon input[type="hidden"]');
        const iconButton = field.querySelector('.modpress-fa-picker-button');
        const iconPreview = field.querySelector('.modpress-fa-picker-preview i');
        const iconContainer = field.querySelector('.modpress-fa-picker-container');
        if (iconHidden) {
            iconHidden.name = inputName;
            iconHidden.value = iconValue;
            iconHidden.disabled = mode !== 'icon';
        }
        if (iconContainer) {
            iconContainer.id = pickerId;
        }
        if (iconButton) {
            iconButton.dataset.pickerId = pickerId;
        }
        if (iconPreview) {
            iconPreview.className = iconValue;
        }

        const iconPane = field.querySelector('.modpress-image-entry-mode-icon');
        const imagePane = field.querySelector('.modpress-image-entry-mode-image');
        if (iconPane) {
            iconPane.style.display = mode === 'icon' ? '' : 'none';
        }
        if (imagePane) {
            imagePane.style.display = mode === 'image' ? '' : 'none';
        }

        return field.outerHTML;
    };

    const renderEntryCustomFields = (modal, currentValues = {}) => {
        const config = modal.dataset.groupFieldConfig ? JSON.parse(modal.dataset.groupFieldConfig) : {};
        const taxonomyKey = modal.querySelector('[name="group_slug"]')?.value || '';
        const container = modal.querySelector('.modpress-entry-custom-fields-container');
        if (!container) {
            return;
        }

        const fields = Array.isArray(config[taxonomyKey]) ? config[taxonomyKey] : [];
        container.innerHTML = '';

        if (!fields.length) {
            container.innerHTML = '<div class="text-muted small">No custom fields configured for this group.</div>';
            return;
        }

        fields.forEach((field) => {
            const slug = String(field.slug || field.name || '').trim();
            if (!slug) {
                return;
            }

            const type = String(field.type || 'text').toLowerCase();
            const label = field.label || field.name || slug;
            const description = field.description || '';
            const inputName = 'entry_custom_field_' + slug;
            const savedValue = currentValues[slug] ?? '';
            let inputMarkup = '<input type="text" name="' + inputName + '" class="form-control w-100" value="' + escapeAttributeValue(savedValue) + '" />';

            if (type === 'textarea') {
                const rows = Number(field.rows) > 0 ? Number(field.rows) : 4;
                inputMarkup = '<textarea name="' + inputName + '" class="form-control w-100" rows="' + rows + '">' + escapeAttributeValue(savedValue) + '</textarea>';
            }

            if (type === 'number') {
                inputMarkup = '<input type="number" name="' + inputName + '" class="form-control w-100" value="' + escapeAttributeValue(savedValue) + '" />';
            }

            if (type === 'switch') {
                const checked = savedValue === true || savedValue === '1' || savedValue === 'yes' || savedValue === 'on' ? ' checked' : '';
                inputMarkup = '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="' + inputName + '" value="1"' + checked + ' /><label class="form-check-label">' + label + '</label></div>';
            }

            if (type === 'image') {
                const allowedModes = Array.isArray(field.image_types) ? field.image_types.map((item) => String(item).toLowerCase()) : ['image'];
                const activeMode = allowedModes.includes('icon') && (String(savedValue || '').startsWith('fa') || String(savedValue || '').includes('fa-')) ? 'icon' : 'image';
                inputMarkup = buildEntryImageSelector(inputName, savedValue, allowedModes.includes('icon') ? activeMode : 'image');
            }

            if (type === 'radio' || type === 'select') {
                const options = Array.isArray(field.options) ? field.options : [];
                if (!options.length) {
                    inputMarkup = '<input type="text" name="' + inputName + '" class="form-control w-100" value="' + escapeAttributeValue(savedValue) + '" />';
                } else if (type === 'radio') {
                    inputMarkup = options.map((option) => {
                        const optionValue = String(option.value || option.label || option);
                        const optionLabel = String(option.label || option.value || option);
                        const checked = String(savedValue) === String(optionValue) ? ' checked' : '';
                        return '<div class="form-check"><input class="form-check-input" type="radio" name="' + inputName + '" value="' + optionValue + '"' + checked + ' /><label class="form-check-label">' + optionLabel + '</label></div>';
                    }).join('');
                } else {
                    inputMarkup = '<select name="' + inputName + '" class="form-select w-100"><option value="">Select...</option>' + options.map((option) => {
                        const optionValue = String(option.value || option.label || option);
                        const optionLabel = String(option.label || option.value || option);
                        const selected = String(savedValue) === String(optionValue) ? ' selected' : '';
                        return '<option value="' + optionValue + '"' + selected + '>' + optionLabel + '</option>';
                    }).join('') + '</select>';
                }
            }

            const fieldRow = document.createElement('div');
            fieldRow.className = 'mb-3';
            fieldRow.innerHTML = '<label class="form-label fw-semibold d-block mb-2">' + label + '</label>' + inputMarkup + (description ? '<div class="form-text mt-2">' + description + '</div>' : '');
            container.appendChild(fieldRow);
        });
    };

    const loadEntryModalData = (trigger) => {
        if (!trigger) {
            return;
        }

        const modalElement = document.getElementById('modpress-entry-modal');
        if (!modalElement) {
            return;
        }

        const taxonomyKey = trigger.dataset.taxonomyKey || '';
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
        renderEntryCustomFields(modalElement, {});

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

    const modalElement = document.getElementById('modpress-entry-modal');
    if (modalElement) {
        modalElement.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }
            loadEntryModalData(trigger);
        });
    }

    bindCustomFieldButtons();
    syncDeleteButtons();
    syncModManagerTabs();
})();