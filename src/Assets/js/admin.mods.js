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

    });

    document.addEventListener('click', (event) => {
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

    const renderEntryCustomFields = (modal) => {
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
            let inputMarkup = '<input type="text" name="' + inputName + '" class="form-control w-100" value="" />';

            if (type === 'textarea') {
                const rows = Number(field.rows) > 0 ? Number(field.rows) : 4;
                inputMarkup = '<textarea name="' + inputName + '" class="form-control w-100" rows="' + rows + '"></textarea>';
            }

            if (type === 'number') {
                inputMarkup = '<input type="number" name="' + inputName + '" class="form-control w-100" value="" />';
            }

            if (type === 'switch') {
                inputMarkup = '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="' + inputName + '" value="1" /><label class="form-check-label">' + label + '</label></div>';
            }

            if (type === 'radio' || type === 'select') {
                const options = Array.isArray(field.options) ? field.options : [];
                if (!options.length) {
                    inputMarkup = '<input type="text" name="' + inputName + '" class="form-control w-100" value="" />';
                } else if (type === 'radio') {
                    inputMarkup = options.map((option) => {
                        const optionValue = String(option.value || option.label || option);
                        const optionLabel = String(option.label || option.value || option);
                        return '<div class="form-check"><input class="form-check-input" type="radio" name="' + inputName + '" value="' + optionValue + '" /><label class="form-check-label">' + optionLabel + '</label></div>';
                    }).join('');
                } else {
                    inputMarkup = '<select name="' + inputName + '" class="form-select w-100"><option value="">Select...</option>' + options.map((option) => {
                        const optionValue = String(option.value || option.label || option);
                        const optionLabel = String(option.label || option.value || option);
                        return '<option value="' + optionValue + '">' + optionLabel + '</option>';
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

            renderEntryCustomFields(modalElement);
        });
    }

    syncDeleteButtons();
    syncModManagerTabs();
})();