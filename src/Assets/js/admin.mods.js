(() => {
    const removeFieldRow = (button) => {
        const row = button.closest('.modpress-custom-field-row');
        if (row && document.querySelectorAll('.modpress-custom-field-row').length > 1) {
            row.remove();
        }
    };

    const syncFieldTypeVisibility = (row) => {
        if (!row) {
            return;
        }

        const typeField = row.querySelector('[data-field-type]');
        const optionWrapper = row.querySelector('[data-role="field-options-wrapper"]');
        const rowsField = row.querySelector('[data-field-rows]');
        const imageTypeField = row.querySelector('[data-field-image-type]');

        if (typeField) {
            const value = String(typeField.value || 'text').toLowerCase();
            if (optionWrapper) {
                optionWrapper.classList.toggle('d-none', !['select', 'radio', 'checkbox'].includes(value));
            }
            if (rowsField) {
                rowsField.closest('tr')?.classList.toggle('d-none', value !== 'textarea');
            }
            if (imageTypeField) {
                imageTypeField.closest('tr')?.classList.toggle('d-none', value !== 'image');
            }
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

    const refreshFieldRowState = () => {
        document.querySelectorAll('.modpress-custom-field-row').forEach((row) => {
            syncFieldTypeVisibility(row);
        });
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
            });
            syncDeleteButtons();
        }

        if (target.matches('[data-field-type]')) {
            syncFieldTypeVisibility(target.closest('.modpress-custom-field-row'));
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
            refreshFieldRowState();
        }

        const removeButton = event.target.closest('[data-role="remove-custom-field"]');
        if (removeButton) {
            removeFieldRow(removeButton);
        }

        const addOptionButton = event.target.closest('[data-role="add-field-option"]');
        if (addOptionButton) {
            const row = addOptionButton.closest('.modpress-custom-field-row');
            const list = row?.querySelector('[data-role="field-options-list"]');
            const template = row?.querySelector('[data-role="field-option-template"]');
            if (!list || !template) {
                return;
            }
            const clone = template.cloneNode(true);
            clone.removeAttribute('data-role');
            clone.classList.remove('d-none');
            list.appendChild(clone);
        }

        const removeOptionButton = event.target.closest('[data-role="remove-field-option"]');
        if (removeOptionButton) {
            const optionRow = removeOptionButton.closest('.modpress-field-option-row');
            if (optionRow && optionRow.parentElement?.querySelectorAll('.modpress-field-option-row').length > 1) {
                optionRow.remove();
            }
        }

        const groupEditButton = event.target.closest('.modpress-tab-edit-btn');
        if (groupEditButton) {
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
            }
        }
    });

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
        });
    }

    syncDeleteButtons();
    syncModManagerTabs();
})();