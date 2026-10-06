document.addEventListener('DOMContentLoaded', () => {
    const root = document;

    const getFieldValue = (form, fieldName) => {
        if (!form || !fieldName) {
            return '';
        }

        const controls = form.querySelectorAll(`[name="${fieldName}"]`);
        const fallback = form.querySelectorAll(`[name="settings[${fieldName}]" ]`);
        const candidates = Array.from(controls).concat(Array.from(fallback));
        const candidate = candidates.find((control) => control && (control.offsetParent !== null || control.closest('.modal')));

        if (!candidate) {
            return '';
        }

        if (candidate.type === 'checkbox') {
            return candidate.checked ? '1' : '0';
        }

        return String(candidate.value ?? '');
    };

    const updateFontAwesomeFieldVisibility = () => {
        root.querySelectorAll('.modpress-plugin-settings-modal').forEach((modal) => {
            const form = modal.querySelector('form[data-plugin-settings-form]');
            if (!form) {
                return;
            }

            const typeValue = getFieldValue(form, 'fontawesome_type');
            const cdnField = form.querySelector('[data-modpress-field-key="fontawesome_cdn_technology"]');
            const kitField = form.querySelector('[data-modpress-field-key="fontawesome_kit_id"]');

            if (cdnField) {
                const showCdn = typeValue === 'cdn';
                cdnField.style.display = showCdn ? '' : 'none';
                cdnField.hidden = !showCdn;
                cdnField.querySelectorAll('input, select, textarea, button').forEach((input) => {
                    input.disabled = !showCdn;
                });
            }

            if (kitField) {
                const showKit = typeValue === 'kit';
                kitField.style.display = showKit ? '' : 'none';
                kitField.hidden = !showKit;
                kitField.querySelectorAll('input, select, textarea, button').forEach((input) => {
                    input.disabled = !showKit;
                });
            }
        });
    };

    root.addEventListener('change', (event) => {
        const target = event.target;
        if (!target || !target.getAttribute) {
            return;
        }

        const name = target.getAttribute('name');
        if (name === 'fontawesome_type' || name === 'settings[fontawesome_type]') {
            updateFontAwesomeFieldVisibility();
        }
    }, true);

    root.addEventListener('input', (event) => {
        const target = event.target;
        if (!target || !target.getAttribute) {
            return;
        }

        const name = target.getAttribute('name');
        if (name === 'fontawesome_type' || name === 'settings[fontawesome_type]') {
            updateFontAwesomeFieldVisibility();
        }
    }, true);

    updateFontAwesomeFieldVisibility();
});
