document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.wp === 'undefined' || !window.wp.media) {
        return;
    }

    const updatePreviewFromValue = (wrapper) => {
        if (!wrapper) {
            return;
        }

        const hiddenInput = wrapper.querySelector('[data-media-field]');
        const previewContainer = wrapper.querySelector('[data-modpress-media-preview-container="true"]');
        const previewImage = wrapper.querySelector('[data-modpress-media-preview-image="true"]');
        const removeButton = wrapper.querySelector('[data-modpress-media-remove]');

        if (!hiddenInput || !previewContainer || !previewImage) {
            return;
        }

        const value = String(hiddenInput.value || '').trim();
        if ('' === value) {
            previewContainer.classList.add('d-none');
            if (removeButton) {
                removeButton.classList.add('d-none');
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
                    previewImage.src = url;
                    previewContainer.classList.remove('d-none');
                    if (removeButton) {
                        removeButton.classList.remove('d-none');
                    }
                },
            });
            return;
        }

        previewImage.src = value;
        previewContainer.classList.remove('d-none');
        if (removeButton) {
            removeButton.classList.remove('d-none');
        }
    };

    const openMediaPicker = (button) => {
        const wrapper = button.closest('[data-modpress-media-wrapper="true"]');
        if (!wrapper) {
            return;
        }

        const hiddenInput = wrapper.querySelector('[data-media-field]');
        const previewContainer = wrapper.querySelector('[data-modpress-media-preview-container="true"]');
        const previewImage = wrapper.querySelector('[data-modpress-media-preview-image="true"]');
        const removeButton = wrapper.querySelector('[data-modpress-media-remove]');
        const mediaType = button.dataset.mediaType || 'image';
        const returnValue = button.dataset.returnValue || 'url';
        const mediaFrame = window.wp.media({
            title: button.dataset.mediaTitle || 'Select Media',
            button: {
                text: button.dataset.mediaButtonText || 'Use Media',
            },
            multiple: false,
            library: {
                type: mediaType,
            },
        });

        mediaFrame.on('select', () => {
            const attachment = mediaFrame.state().get('selection').first();
            if (!attachment) {
                return;
            }

            const selected = attachment.toJSON();
            const nextValue = 'id' === returnValue ? String(selected.id || '') : (selected.url || selected.sizes?.full?.url || selected.icon || '');

            if (hiddenInput) {
                hiddenInput.value = nextValue;
                hiddenInput.dataset.mediaValueType = returnValue;
            }

            if (previewImage) {
                previewImage.src = nextValue;
            }

            if (previewContainer) {
                previewContainer.classList.toggle('d-none', !nextValue);
            }

            if (removeButton) {
                removeButton.classList.toggle('d-none', !nextValue);
            }

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

        const hiddenInput = wrapper.querySelector('[data-media-field]');
        const previewContainer = wrapper.querySelector('[data-modpress-media-preview-container="true"]');
        const previewImage = wrapper.querySelector('[data-modpress-media-preview-image="true"]');

        if (hiddenInput) {
            hiddenInput.value = '';
        }

        if (previewImage) {
            previewImage.src = '';
        }

        if (previewContainer) {
            previewContainer.classList.add('d-none');
        }

        removeButton.classList.add('d-none');
    });

    document.querySelectorAll('[data-modpress-media-wrapper="true"]').forEach((wrapper) => {
        updatePreviewFromValue(wrapper);
    });
});
