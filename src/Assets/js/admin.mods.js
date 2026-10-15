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

        if (target.matches('[data-field-type]')) {
            const row = target.closest('.modpress-custom-field-row');
            const fieldOptions = row ? row.querySelector('.modpress-field-options') : null;
            if (!fieldOptions) {
                return;
            }
            const showOptions = ['select', 'radio', 'checkbox'].includes(target.value.toLowerCase());
            fieldOptions.classList.toggle('is-visible', showOptions);
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
        }

        const removeButton = event.target.closest('[data-role="remove-custom-field"]');
        if (removeButton) {
            removeFieldRow(removeButton);
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
            if (nameField && trigger.dataset.entryName) {
                nameField.value = trigger.dataset.entryName;
            }
            const slugField = modalElement.querySelector('[name="entry_slug"]');
            if (slugField && trigger.dataset.entrySlug) {
                slugField.value = trigger.dataset.entrySlug;
            }
            const descriptionField = modalElement.querySelector('[name="entry_description"]');
            if (descriptionField && trigger.dataset.entryDescription) {
                descriptionField.value = trigger.dataset.entryDescription;
            }
            const parentField = modalElement.querySelector('[name="entry_parent"]');
            if (parentField && trigger.dataset.entryParent) {
                parentField.value = trigger.dataset.entryParent;
            }
            const iconField = modalElement.querySelector('[name="entry_icon"]');
            if (iconField && trigger.dataset.entryIcon) {
                iconField.value = trigger.dataset.entryIcon;
            }
        });
    }

    syncDeleteButtons();
})();