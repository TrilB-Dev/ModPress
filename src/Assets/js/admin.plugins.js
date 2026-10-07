document.addEventListener('DOMContentLoaded', () => {
    const root = document;
    const config = window.modpressSettingsTabs;
    if (!config) {
        console.warn('[ModPress] Missing modpressSettingsTabs config. Check that the settings page enqueues the admin plugin script.');
        return;
    }

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

    const debugLog = (...args) => {
        if (typeof console !== 'undefined') {
            console.log('[ModPress]', ...args);
        }
    };

    const savePluginSettings = (source) => {
        const button = source instanceof HTMLElement ? source : null;
        const modal = button ? button.closest('.modpress-plugin-settings-modal') : source?.closest?.('.modpress-plugin-settings-modal');
        const form = button ? button.form || modal?.querySelector('[data-plugin-settings-form]') : source instanceof HTMLFormElement ? source : modal?.querySelector('[data-plugin-settings-form]');

        if (!modal || !form) {
            debugLog('savePluginSettings: missing modal or form', { source, modal: !!modal, form: !!form });
            return;
        }

        const saveButton = button || modal.querySelector('[data-plugin-settings-save]');
        if (saveButton) {
            setButtonSaving(saveButton);
        }

        debugLog('savePluginSettings: starting save', {
            slug: form.dataset.pluginSlug || '',
            action: 'modpress_save_plugin_settings',
        });

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        const settings = {};

        for (const [key, value] of Object.entries(payload)) {
            if (key.startsWith('settings[') || key.startsWith('plugin_settings[')) {
                const match = key.match(/^settings\[(.*)\]$|^plugin_settings\[(.*)\]$/);
                if (match) {
                    const fieldName = match[1] || match[2] || '';
                    if (fieldName) {
                        settings[fieldName] = value;
                    }
                }
            }
        }

        debugLog('savePluginSettings: parsed payload', { formDataKeys: Object.keys(payload), settings });

        const body = new URLSearchParams();
        body.set('action', 'modpress_save_plugin_settings');
        body.set('nonce', config.pluginSettingsNonce || '');
        body.set('slug', form.dataset.pluginSlug || '');
        body.set('settings', JSON.stringify(settings));
        body.set('plugin_settings', JSON.stringify(settings));

        debugLog('savePluginSettings: sending request', {
            url: config.ajaxUrl,
            slug: form.dataset.pluginSlug || '',
            settings
        });

        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body
        })
        .then((response) => {
            debugLog('savePluginSettings: response received', { status: response.status, ok: response.ok });
            return response.json();
        })
        .then((response) => {
            debugLog('savePluginSettings: response payload', response);
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
            console.error('[ModPress] savePluginSettings: failed', error);
            if (saveButton) {
                resetSavingButton(saveButton);
            }
            showPluginNotice(error.alert);
        })
        .finally(() => {
            if (saveButton) {
                resetSavingButton(saveButton);
            }
        });
    };

    const getScopeControl = (field, key) => {
        const scopes = [field.closest('form'), document];
        for (const scope of scopes) {
            if (!scope) continue;
            const candidates = scope.querySelectorAll('[name]');
            for (const candidate of candidates) {
                const name = candidate.getAttribute('name') || '';
                if (name === key || name === `settings[${key}]`) {
                    return candidate;
                }
            }
        }

        return null;
    };

    const getFormControlValue = (control) => {
        if (!control) {
            return '';
        }

        if (control.type === 'checkbox') {
            return control.checked ? '1' : '0';
        }

        if (control.type === 'radio') {
            const checked = control.form?.querySelector(`input[name="${control.name}"]:checked`);
            return checked ? String(checked.value ?? '') : '';
        }

        return String(control.value ?? '');
    };

    const matchesSingleRule = (field, key, expected) => {
        const input = getScopeControl(field, key);
        const actual = getFormControlValue(input);

        if (Array.isArray(expected)) {
            return expected.some((candidate) => String(candidate) === actual);
        }

        return String(expected ?? '') === actual;
    };

    const matchesCondition = (field, ruleSet) => {
        if (!ruleSet || typeof ruleSet !== 'object') {
            return true;
        }

        if (Array.isArray(ruleSet)) {
            if (ruleSet.length === 0) {
                return true;
            }

            return ruleSet.some((item) => matchesCondition(field, item));
        }

        return Object.entries(ruleSet).every(([key, expected]) => matchesSingleRule(field, key, expected));
    };

    const applyConditionalFieldVisibility = () => {
        root.querySelectorAll('[data-modpress-visible-when], [data-modpress-required-when]').forEach((field) => {
            const visibleRule = field.dataset.modpressVisibleWhen ? JSON.parse(field.dataset.modpressVisibleWhen) : null;
            const requiredRule = field.dataset.modpressRequiredWhen ? JSON.parse(field.dataset.modpressRequiredWhen) : null;
            const visible = matchesCondition(field, visibleRule) || !visibleRule;
            const required = matchesCondition(field, requiredRule) || !requiredRule;
            const showField = visible && required;

            field.style.display = showField ? '' : 'none';
            field.hidden = !showField;

            field.querySelectorAll('input, select, textarea, button').forEach((input) => {
                input.disabled = !showField;
            });
        });
    };

    //
    // --- EVENT LISTENERS ---
    //

    root.addEventListener('submit', (event) => {
        const form = event.target.closest?.('[data-plugin-settings-form]');
        if (!form) {
            return;
        }

        event.preventDefault();
        savePluginSettings(form);
    }, true);

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
            event.preventDefault();
            savePluginSettings(save);
        }
    }, true);

    root.addEventListener('change', (event) => {
        const toggle = event.target.closest?.('[data-modpress-plugin-toggle]');
        if (toggle) {
            togglePlugin(toggle);
        }

        const target = event.target;
        const fieldName = target && target.getAttribute ? target.getAttribute('name') : '';
        if (fieldName && (fieldName.startsWith('settings[') || fieldName === 'fontawesome_type')) {
            applyConditionalFieldVisibility();
        }
    }, true);

    root.addEventListener('input', (event) => {
        const target = event.target;
        const fieldName = target && target.getAttribute ? target.getAttribute('name') : '';
        if (fieldName && (fieldName.startsWith('settings[') || fieldName === 'fontawesome_type')) {
            applyConditionalFieldVisibility();
        }
    }, true);

    applyConditionalFieldVisibility();
});
