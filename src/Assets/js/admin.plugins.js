document.addEventListener('DOMContentLoaded', () => {
    const root = document;
    const config = window.modpressSettingsTabs;
    if (!config) return;

    //
    // --- MODAL HANDLING (Bootstrap-native) ---
    //

    const cleanupModalArtifacts = () => {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        root.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());

        root.querySelectorAll('.modal.show').forEach((shownModal) => {
            shownModal.classList.remove('show');
            shownModal.style.removeProperty('display');
            shownModal.removeAttribute('aria-modal');
            shownModal.removeAttribute('role');
            shownModal.setAttribute('aria-hidden', 'true');
        });
    };

    const closePluginModal = (modal) => {
        if (!modal) {
            return Promise.resolve();
        }

        const instance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
        return new Promise((resolve) => {
            const finish = () => {
                root.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
                modal.classList.remove('show');
                modal.style.removeProperty('display');
                modal.removeAttribute('aria-modal');
                modal.removeAttribute('role');
                modal.setAttribute('aria-hidden', 'true');
                instance.dispose();
                resolve();
            };

            modal.addEventListener('hidden.bs.modal', finish, { once: true });
            instance.hide();

            setTimeout(() => {
                if (!modal.classList.contains('show')) {
                    finish();
                }
            }, 300);
        });
    };

    const showPluginNotice = (alertMarkup) => {
        const container = root.querySelector('#modpress-settings-panel .modpress-settings-tab-content');
        if (!container || !alertMarkup) return;

        container.querySelectorAll('[data-modpress-alert]').forEach((notice) => notice.remove());
        container.insertAdjacentHTML('afterbegin', alertMarkup);
    };

    const setButtonSaving = (button) => {
        if (!button) {
            return;
        }

        if (!button.dataset.originalHtml) {
            button.dataset.originalHtml = button.innerHTML;
        }

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + (button.dataset.savingText || 'Saving...');
    };

    const resetSavingButton = (button) => {
        if (!button || !button.dataset.originalHtml) {
            return;
        }

        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.innerHTML = button.dataset.originalHtml;
    };

    //
    // --- PLUGIN TOGGLE ---
    //

    const togglePlugin = (toggle) => {
        const enabled = toggle.checked;
        toggle.disabled = true;

        const body = new URLSearchParams({
            action: 'modpress_toggle_plugin',
            nonce: config.pluginNonce,
            slug: toggle.dataset.pluginSlug || '',
            enabled: enabled ? '1' : '0'
        });

        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body
        })
        .then((response) => response.json())
        .then((response) => {
            if (!response.success) throw new Error('Unable to save plugin state');
        })
        .catch(() => {
            toggle.checked = !enabled;
        })
        .finally(() => {
            toggle.disabled = false;
        });
    };

    //
    // --- SAVE PLUGIN SETTINGS ---
    //

    const savePluginSettings = (button) => {
        const modal = button.closest('.modpress-plugin-settings-modal');
        const form = modal?.querySelector('[data-plugin-settings-form]');
        if (!modal || !form) return;

        setButtonSaving(button);

        const body = new URLSearchParams(new FormData(form));
        body.set('action', 'modpress_save_plugin_settings');
        body.set('nonce', config.pluginSettingsNonce);
        body.set('slug', form.dataset.pluginSlug || '');

        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body
        })
        .then((response) => response.json())
        .then((response) => {
            if (!response.success) {
                const error = new Error(response.data?.message || 'Unable to save plugin settings');
                error.alert = response.data?.alert;
                throw error;
            }
            return closePluginModal(modal).then(() => {
                window.location.reload();
            });
        })
        .catch((error) => {
            resetSavingButton(button);
            showPluginNotice(error.alert);
        })
        .finally(() => {
            resetSavingButton(button);
        });
    };

    //
    // --- EVENT LISTENERS ---
    //

    root.addEventListener('click', (event) => {
        // Open modal
        const trigger = event.target.closest?.('[data-bs-toggle="modal"][data-bs-target]');
        if (trigger) {
            return; // Let Bootstrap handle native modal opening
        }

        // Close modal
        const dismiss = event.target.closest?.('.modpress-plugin-settings-modal [data-bs-dismiss="modal"]');
        if (dismiss) {
            event.preventDefault();
            const modal = dismiss.closest('.modpress-plugin-settings-modal');
            closePluginModal(modal);
            return;
        }

        // Save settings
        const save = event.target.closest?.('[data-plugin-settings-save]');
        if (save) {
            savePluginSettings(save);
        }
    }, true);

    root.addEventListener('change', (event) => {
        const toggle = event.target.closest?.('[data-modpress-plugin-toggle]');
        if (toggle) {
            togglePlugin(toggle);
        }
    }, true);
});
