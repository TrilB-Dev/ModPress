document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.wp === 'undefined' || !window.wp.media) {
        console.error('ModPress media picker: WordPress media library is not available.');
        return;
    }

    const getMediaTarget = (button) => {
        const wrapper = button.closest('[data-modpress-media-wrapper="true"]');
        if (!wrapper) {
            return null;
        }

        const targetName = button.dataset.mediaTarget || button.dataset.mediaFieldName || '';
        const targetId = button.dataset.mediaFieldId || '';

        const targetByName = targetName ? wrapper.querySelector(`[name="${CSS.escape(targetName)}"]`) : null;
        const targetById = targetId ? wrapper.querySelector(`#${CSS.escape(targetId)}`) : null;
        const targetField = wrapper.querySelector('[data-media-field="true"]') || targetByName || targetById || null;

        return {
            wrapper,
            field: targetField,
        };
    };

    const syncMediaPreview = (wrapper, nextValue) => {
        const previewContainer = wrapper.querySelector('[data-modpress-media-preview-container="true"]');
        const previewImage = wrapper.querySelector('[data-modpress-media-preview-image="true"]');
        const removeButton = wrapper.querySelector('[data-modpress-media-remove]');

        if (previewImage) {
            previewImage.src = nextValue || '';
        }

        if (previewContainer) {
            previewContainer.classList.toggle('d-none', !nextValue);
        }

        if (removeButton) {
            removeButton.classList.toggle('d-none', !nextValue);
        }
    };

    const openMediaPicker = (button) => {
        const mediaTarget = getMediaTarget(button);
        if (!mediaTarget || !mediaTarget.field) {
            console.error('ModPress media picker: no target field found for this button.');
            return;
        }

        const { wrapper, field } = mediaTarget;
        const mediaFrame = window.wp.media({
            title: button.dataset.mediaTitle || 'Select Media',
            button: {
                text: button.dataset.mediaButtonText || 'Use Media',
            },
            multiple: false,
            library: {
                type: button.dataset.mediaType || 'image',
            },
        });

        mediaFrame.on('select', () => {
            const attachment = mediaFrame.state().get('selection').first();
            if (!attachment) {
                console.error('ModPress media picker: no media selected.');
                return;
            }

            const selected = attachment.toJSON();
            const nextValue = button.dataset.returnValue === 'id'
                ? String(selected.id || '')
                : (selected.url || selected.sizes?.full?.url || selected.icon || '');

            field.value = nextValue;
            field.dataset.mediaValueType = button.dataset.returnValue || 'url';
            field.dispatchEvent(new Event('input', { bubbles: true }));

            syncMediaPreview(wrapper, nextValue);

            window.setTimeout(() => {
                if (typeof mediaFrame.close === 'function') {
                    mediaFrame.close();
                }
            }, 0);
        });

        mediaFrame.open();
    };

    document.addEventListener('click', (event) => {
        const pickerButton = event.target.closest('[data-modpress-media-picker="true"]');
        if (pickerButton) {
            event.preventDefault();
            event.stopPropagation();
            openMediaPicker(pickerButton);
            return;
        }

        const removeButton = event.target.closest('[data-modpress-media-remove]');
        if (!removeButton) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const wrapper = removeButton.closest('[data-modpress-media-wrapper="true"]');
        if (!wrapper) {
            return;
        }

        const hiddenField = wrapper.querySelector('[data-media-field="true"]');
        if (hiddenField) {
            hiddenField.value = '';
        }

        syncMediaPreview(wrapper, '');
    });

    document.querySelectorAll('[data-modpress-media-wrapper="true"]').forEach((wrapper) => {
        const hiddenField = wrapper.querySelector('[data-media-field="true"]');
        if (!hiddenField) {
            return;
        }

        const value = String(hiddenField.value || '').trim();
        const previewContainer = wrapper.querySelector('[data-modpress-media-preview-container="true"]');
        const previewImage = wrapper.querySelector('[data-modpress-media-preview-image="true"]');

        if (!value) {
            if (previewContainer) {
                previewContainer.classList.add('d-none');
            }
            return;
        }

        if (/^\d+$/.test(value)) {
            const attachment = window.wp.media.attachment(value);
            attachment.fetch({
                success: (model) => {
                    const url = model && model.attributes && model.attributes.url ? model.attributes.url : '';
                    if (!url) {
                        return;
                    }

                    if (previewImage) {
                        previewImage.src = url;
                    }

                    if (previewContainer) {
                        previewContainer.classList.remove('d-none');
                    }
                },
            });
            return;
        }

        if (previewImage) {
            previewImage.src = value;
        }

        if (previewContainer) {
            previewContainer.classList.remove('d-none');
        }
    });
});