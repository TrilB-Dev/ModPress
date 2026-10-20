(() => {
    const removeFieldRow = (button) => {
        const row = button.closest('.modpress-custom-field-row');
        if (row && document.querySelectorAll('.modpress-custom-field-row').length > 1) {
            row.remove();
        }
    };

    const syncDeleteButtons = () => {
        const rows = document.querySelectorAll('.modpress-row-checkbox');
        const button = document.querySelector('.btn-delete-selected');
        if (!button) {
            return;
        }
        const hasSelection = Array.from(rows).some((checkbox) => checkbox.checked);
        button.disabled = !hasSelection;
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

    const syncGroupParentField = (groupKey = '') => {
        const row = document.getElementById('modpress-group-parent-row');
        const select = document.getElementById('group_parent');
        if (!row || !select) {
            return;
        }

        const isReserved = String(groupKey || '').trim() === 'modpress_type';
        row.classList.toggle('d-none', isReserved);
        if (isReserved) {
            select.value = '';
        }
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

    const populateCustomFieldRows = (groupData = {}) => {
        const modal = document.getElementById('modpress-group-modal');
        if (!modal) {
            return;
        }

        const container = modal.querySelector('#modpress-custom-fields-container');
        if (!container) {
            return;
        }

        const template = document.getElementById('modpress-custom-field-template');
        if (!template) {
            return;
        }

        container.innerHTML = '';
        const fields = getGroupFields(groupData);

        if (!fields.length) {
            container.innerHTML = '<div class="text-muted small">No custom fields configured for this group.</div>';
            return;
        }

        fields.forEach((field) => {
            const row = template.cloneNode(true);
            row.removeAttribute('id');
            row.classList.remove('d-none');
            row.classList.add('modpress-custom-field-row');

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
                    options.forEach((option) => {
                        const optionRow = optionTemplate.cloneNode(true);
                        optionRow.classList.remove('d-none');
                        optionRow.classList.remove('modpress-option-row-template');
                        optionRow.classList.add('modpress-option-row');

                        const labelInputField = optionRow.querySelector('[name="custom_field_type_radio[][label]"]') || optionRow.querySelector('[name="custom_field_type_select[][label]"]');
                        const optionInputField = optionRow.querySelector('[name="custom_field_type_radio[][option]"]') || optionRow.querySelector('[name="custom_field_type_select[][option]"]');

                        if (labelInputField) {
                            labelInputField.value = option.label || option.name || '';
                        }

                        if (optionInputField) {
                            optionInputField.value = option.value || option.option || option.label || '';
                        }

                        optionItems.appendChild(optionRow);
                    });
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
            syncCustomFieldVisibility(row);
        });
    };

    const resetGroupModal = () => {
        const modal = document.getElementById('modpress-group-modal');
        if (!modal) {
            return;
        }

        const fields = [
            'group_edit_key',
            'group_name',
            'group_singular_name',
            'group_slug',
            'group_description',
            'group_parent',
        ];

        fields.forEach((fieldName) => {
            const input = modal.querySelector('[name="' + fieldName + '"]');
            if (input) {
                input.value = '';
            }
        });

        const hierarchicalInput = modal.querySelector('[name="group_hierarchical"]');
        if (hierarchicalInput) {
            hierarchicalInput.checked = false;
        }

        const modalTitle = modal.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = 'Add Group';
        }

        syncGroupParentField('');
    };

    const populateGroupModal = (groupData = {}) => {
        const modal = document.getElementById('modpress-group-modal');
        if (!modal) {
            return;
        }

        const groupKey = String(groupData.group_key || groupData.group_slug || groupData.taxonomy || '').trim();
        const hiddenField = modal.querySelector('[name="group_edit_key"]');
        if (hiddenField) {
            hiddenField.value = groupKey;
        }

        const modalTitle = modal.querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.textContent = groupKey ? 'Edit Group' : 'Add Group';
        }

        const fieldMap = {
            group_name: groupData.group_name || '',
            group_singular_name: groupData.group_singular_name || '',
            group_slug: groupData.group_slug || '',
            group_description: groupData.group_description || '',
        };

        Object.entries(fieldMap).forEach(([name, value]) => {
            const input = modal.querySelector('[name="' + name + '"]');
            if (input) {
                input.value = value || '';
            }
        });

        const hierarchicalInput = modal.querySelector('[name="group_hierarchical"]');
        if (hierarchicalInput) {
            hierarchicalInput.checked = Boolean(groupData.group_hierarchical || groupData.hierarchical);
        }

        const parentSelect = modal.querySelector('[name="group_parent"]');
        if (parentSelect) {
            const parentValue = String(groupData.group_parent || groupData.group_parent_id || '').trim();
            parentSelect.value = '';
            if (parentValue && String(groupKey).trim() !== 'modpress_type') {
                parentSelect.value = parentValue;
            }
        }

        populateCustomFieldRows(groupData);
        syncGroupParentField(groupKey);
    };

    const addOptionRow = (button) => {
        const repeater = button.closest('.modpress-option-repeater');
        if (!repeater) {
            return;
        }

        const template = repeater.querySelector('.modpress-option-row-template');
        if (!template) {
            return;
        }

        const clone = template.cloneNode(true);
        clone.classList.remove('d-none');
        clone.classList.remove('modpress-option-row-template');
        clone.classList.add('modpress-option-row');
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

        const button = event.target.closest('[data-role="add-custom-field"]');
        if (button) {
            const container = document.getElementById('modpress-custom-fields-container');
            if (!container) {
                return;
            }
            const template = document.getElementById('modpress-custom-field-template');
            if (!template) {
                return;
            }
            const clone = template.cloneNode(true);
            clone.removeAttribute('id');
            clone.classList.remove('d-none');
            container.appendChild(clone);
            syncCustomFieldVisibility(clone);
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

        const removeButton = event.target.closest('[data-role="remove-custom-field"]');
        if (removeButton) {
            removeFieldRow(removeButton);
        }

        const groupEditButton = event.target.closest('.modpress-tab-edit-btn');
        if (groupEditButton) {
            const rawGroupData = groupEditButton.dataset.groupData || '{}';
            try {
                const parsedData = JSON.parse(rawGroupData);
                populateGroupModal(parsedData);
            } catch (error) {
                const modal = document.getElementById('modpress-group-modal');
                if (modal) {
                    const title = modal.querySelector('.modal-title');
                    const groupKey = groupEditButton.dataset.groupKey;
                    const groupLabel = groupEditButton.dataset.groupLabel || '';
                    if (title) {
                        title.textContent = 'Edit Group';
                    }
                    const hiddenField = modal.querySelector('[name="group_edit_key"]');
                    if (hiddenField) {
                        hiddenField.value = groupKey || '';
                    }
                    const labelField = modal.querySelector('[name="group_name"]');
                    if (labelField) {
                        labelField.value = groupLabel;
                    }
                    syncGroupParentField(groupKey || '');
                }
            }
        }
    });

    const escapeAttributeValue = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const buildEntryImageSelector = (inputName, value, mode = 'image') => {
        const sanitizedValue = String(value || '');
        const imageValue = sanitizedValue && mode !== 'icon' ? sanitizedValue : '';
        const iconValue = mode === 'icon' && sanitizedValue ? sanitizedValue : 'fas fa-image';
        const pickerId = 'modpress-entry-icon-picker-' + inputName.replace(/[^a-zA-Z0-9_-]/g, '-');

        return `
            <div class="modpress-image-field" data-input-name="${escapeAttributeValue(inputName)}">
                <div class="mb-2">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input modpress-image-picker-mode-input" type="radio" name="${inputName}_mode" value="image" ${mode === 'image' ? 'checked' : ''} data-input-name="${escapeAttributeValue(inputName)}" />
                        <label class="form-check-label">Image</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input modpress-image-picker-mode-input" type="radio" name="${inputName}_mode" value="icon" ${mode === 'icon' ? 'checked' : ''} data-input-name="${escapeAttributeValue(inputName)}" />
                        <label class="form-check-label">Icon</label>
                    </div>
                </div>

                <div class="modpress-image-entry-mode modpress-image-entry-mode-image" ${mode === 'image' ? '' : 'style="display:none;"'}>
                    <input type="hidden" name="${inputName}" value="${escapeAttributeValue(imageValue)}" ${mode === 'image' ? '' : 'disabled'} />
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-secondary btn-sm modpress-image-select">Select Image</button>
                        <button type="button" class="btn btn-outline-danger btn-sm modpress-image-clear ${imageValue ? '' : 'd-none'}">Remove</button>
                    </div>
                    <div class="modpress-image-preview mt-2 ${imageValue ? '' : 'd-none'}">
                        <img src="${escapeAttributeValue(imageValue)}" alt="" class="img-thumbnail" style="max-height:120px;max-width:220px;object-fit:cover;" />
                    </div>
                </div>

                <div class="modpress-image-entry-mode modpress-image-entry-mode-icon" ${mode === 'icon' ? '' : 'style="display:none;"'}>
                    <div class="modpress-fa-picker-container" id="${pickerId}">
                        <input type="hidden" name="${inputName}" value="${escapeAttributeValue(iconValue)}" ${mode === 'icon' ? '' : 'disabled'} />
                        <button type="button" class="btn btn-outline-secondary modpress-fa-picker-button" data-picker-id="${pickerId}">
                            <span class="modpress-fa-picker-preview">
                                <i class="${escapeAttributeValue(iconValue)}" style="font-size: 1.25rem;"></i>
                            </span>
                            <span class="modpress-fa-picker-text">Choose Icon</span>
                        </button>
                        <div class="modpress-fa-picker-modal" style="display: none;">
                            <div class="modpress-fa-picker-overlay"></div>
                            <div class="modpress-fa-picker-dialog">
                                <div class="modpress-fa-picker-header">
                                    <h3>Choose an Icon</h3>
                                    <button type="button" class="modpress-fa-picker-close">&times;</button>
                                </div>
                                <div class="modpress-fa-picker-search">
                                    <input type="search" class="form-control modpress-fa-picker-search-input" placeholder="Search icons..." />
                                    <select class="form-select modpress-fa-picker-pack-filter">
                                        <option value="classic">Classic</option>
                                        <option value="brands">Brands</option>
                                    </select>
                                    <select class="form-select modpress-fa-picker-style-filter">
                                        <option value="solid">Solid</option>
                                        <option value="regular">Regular</option>
                                        <option value="light">Light</option>
                                        <option value="thin">Thin</option>
                                    </select>
                                </div>
                                <div class="modpress-fa-picker-results"><div class="modpress-fa-picker-loading">Loading...</div></div>
                                <div class="modpress-fa-picker-footer">
                                    <button type="button" class="btn btn-secondary modpress-fa-picker-cancel">Cancel</button>
                                    <button type="button" class="btn btn-primary modpress-fa-picker-select" disabled>Select Icon</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
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

    const groupModalElement = document.getElementById('modpress-group-modal');
    if (groupModalElement) {
        groupModalElement.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const isAddGroup = Boolean(trigger && trigger.matches('[data-group-add="true"]'));
            const isEditGroup = Boolean(trigger && trigger.matches('.modpress-tab-edit-btn'));

            if (!trigger || isAddGroup) {
                resetGroupModal();
                return;
            }

            if (!isEditGroup) {
                resetGroupModal();
                return;
            }

            const rawGroupData = trigger.dataset.groupData || '{}';
            try {
                const parsedData = JSON.parse(rawGroupData);
                populateGroupModal(parsedData);
            } catch (error) {
                resetGroupModal();
            }
        });
    }

    const modalElement = document.getElementById('modpress-entry-modal');
    if (modalElement) {
        modalElement.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!trigger) {
                return;
            }
            const taxonomyKey = trigger.dataset.taxonomyKey || '';
            const taxonomyLabel = trigger.dataset.taxonomyLabel || 'Group';
            const title = modalElement.querySelector('.modal-title');
            if (title) {
                const isEdit = Boolean(trigger.dataset.entryId);
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
            const entryIdField = modalElement.querySelector('[name="group_entry_id"]');
            if (entryIdField) {
                entryIdField.value = trigger.dataset.entryId || '';
            }
            const nameField = modalElement.querySelector('[name="entry_name"]');
            if (nameField) {
                nameField.value = trigger.dataset.entryName || '';
            }
            const slugField = modalElement.querySelector('[name="entry_slug"]');
            if (slugField) {
                slugField.value = trigger.dataset.entrySlug || '';
            }
            const descriptionField = modalElement.querySelector('[name="entry_description"]');
            if (descriptionField) {
                descriptionField.value = trigger.dataset.entryDescription || '';
            }
            const parentField = modalElement.querySelector('[name="entry_parent"]');
            if (parentField) {
                parentField.value = trigger.dataset.entryParent || '0';
            }
            const iconField = modalElement.querySelector('[name="entry_icon"]');
            if (iconField) {
                iconField.value = trigger.dataset.entryIcon || '';
            }

            let entryCustomFields = {};
            try {
                entryCustomFields = trigger.dataset.entryCustomFields ? JSON.parse(trigger.dataset.entryCustomFields) : {};
            } catch (error) {
                entryCustomFields = {};
            }

            renderEntryCustomFields(modalElement, entryCustomFields);
        });
    }

    syncDeleteButtons();
    syncModManagerTabs();
})();