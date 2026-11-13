document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.wp === 'undefined' || !window.wp.media) {
        console.error('ModPress media picker: WordPress media library is not available.');
        return;
    }

    const parseMediaValues = (value) => {
        if (Array.isArray(value)) {
            return value.flatMap((entry) => parseMediaValues(entry));
        }

        if (typeof value !== 'string') {
            return [];
        }

        return value
            .split(',')
            .map((entry) => entry.trim())
            .filter(Boolean);
    };

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
        const previewList = wrapper.querySelector('[data-modpress-media-preview-list="true"]');
        const removeButton = wrapper.querySelector('[data-modpress-media-remove]');
        const hiddenField = wrapper.querySelector('[data-media-field="true"]');
        const isMultiple = hiddenField && hiddenField.dataset.multiple === 'true';

        const values = parseMediaValues(nextValue);

        if (isMultiple) {
            const urlValues = values
                .filter((entry) => !/^\d+$/.test(entry))
                .slice(0, 6);

            if (previewList) {
                previewList.innerHTML = urlValues
                    .map((src) => `<img src="${src}" alt="Media preview" class="img-fluid rounded" style="max-width:120px; max-height:120px; width:auto; height:auto; object-fit:cover;" data-modpress-media-preview-image="true" />`)
                    .join('');
            }

            if (previewContainer) {
                previewContainer.classList.toggle('d-none', values.length === 0 && urlValues.length === 0);
            }

            if (removeButton) {
                removeButton.classList.toggle('d-none', values.length === 0 && urlValues.length === 0);
            }

            return;
        }

        const firstValue = values.length > 0 ? values[0] : '';
        if (previewImage) {
            previewImage.src = firstValue || '';
        }

        if (previewContainer) {
            previewContainer.classList.toggle('d-none', !firstValue);
        }

        if (removeButton) {
            removeButton.classList.toggle('d-none', !firstValue);
        }
    };

    let activeMediaFrame = null;

    const closeMediaFrame = (mediaFrame) => {
        if (!mediaFrame) {
            return;
        }

        try {
            if (typeof mediaFrame.close === 'function') {
                mediaFrame.close();
            }

            const frameRoot = mediaFrame.$el ||
                (mediaFrame.content && mediaFrame.content.get ? mediaFrame.content.get().$el : null) ||
                document.querySelector('.media-modal');

            if (frameRoot && typeof frameRoot.hide === 'function') {
                frameRoot.hide();
            }

            if (frameRoot && typeof frameRoot.modal === 'function') {
                frameRoot.modal('hide');
            }

            const modalBackdrop = document.querySelector('.media-modal-backdrop');
            if (modalBackdrop && modalBackdrop.parentNode) {
                modalBackdrop.parentNode.removeChild(modalBackdrop);
            }

            const modalElement = document.querySelector('.media-modal');
            if (modalElement) {
                modalElement.style.display = 'none';
                modalElement.classList.remove('open');
                modalElement.setAttribute('aria-hidden', 'true');
            }

            if (typeof mediaFrame.dispose === 'function') {
                mediaFrame.dispose();
            }
        } catch (error) {
            console.error('ModPress media picker: could not close media frame.', error);
        }
    };

    const openMediaPicker = (button) => {
        const mediaTarget = getMediaTarget(button);
        if (!mediaTarget || !mediaTarget.field) {
            console.error('ModPress media picker: no target field found for this button.');
            return;
        }

        const { wrapper, field } = mediaTarget;
        const isMultiple = button.dataset.multiple === 'true' || field.dataset.multiple === 'true';
        const mediaFrame = window.wp.media({
            title: button.dataset.mediaTitle || 'Select Media',
            button: {
                text: button.dataset.mediaButtonText || 'Use Media',
            },
            multiple: isMultiple,
            library: {
                type: button.dataset.mediaType || 'image',
            },
        });

        activeMediaFrame = mediaFrame;

        mediaFrame.on('select', () => {
            const selection = mediaFrame.state().get('selection');
            const attachments = selection ? selection.toArray() : [];

            if (attachments.length === 0) {
                console.error('ModPress media picker: no media selected.');
                return;
            }

            const values = attachments.map((attachment) => {
                const selected = attachment.toJSON();
                if (button.dataset.returnValue === 'id') {
                    return String(selected.id || '');
                }

                return selected.url || selected.sizes?.full?.url || selected.icon || '';
            }).filter(Boolean);

            const nextValue = isMultiple ? values.join(',') : (values[0] || '');

            field.value = nextValue;
            field.dataset.mediaValueType = button.dataset.returnValue || 'url';
            field.dataset.multiple = isMultiple ? 'true' : 'false';
            field.dispatchEvent(new Event('input', { bubbles: true }));

            syncMediaPreview(wrapper, nextValue);

            window.setTimeout(() => {
                closeMediaFrame(mediaFrame);
                activeMediaFrame = null;
            }, 0);
        });

        mediaFrame.on('close', () => {
            if (activeMediaFrame === mediaFrame) {
                activeMediaFrame = null;
            }
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
        const previewList = wrapper.querySelector('[data-modpress-media-preview-list="true"]');
        const previewImage = wrapper.querySelector('[data-modpress-media-preview-image="true"]');
        const values = parseMediaValues(value);

        if (!values.length) {
            if (previewContainer) {
                previewContainer.classList.add('d-none');
            }
            return;
        }

        const isMultiple = hiddenField.dataset.multiple === 'true';

        if (isMultiple) {
            const firstNumericId = values.find((entry) => /^\d+$/.test(entry));
            if (firstNumericId && typeof window.wp.media.attachment === 'function') {
                const attachment = window.wp.media.attachment(firstNumericId);
                attachment.fetch({
                    success: (model) => {
                        const url = model && model.attributes && model.attributes.url ? model.attributes.url : '';
                        if (url && previewList) {
                            previewList.innerHTML = `<img src="${url}" alt="Media preview" class="img-fluid rounded" style="max-width:120px; max-height:120px; width:auto; height:auto; object-fit:cover;" data-modpress-media-preview-image="true" />`;
                        }

                        if (previewContainer) {
                            previewContainer.classList.remove('d-none');
                        }
                    },
                });
                return;
            }

            const urlValues = values.filter((entry) => !/^\d+$/.test(entry)).slice(0, 6);
            if (previewList) {
                previewList.innerHTML = urlValues
                    .map((src) => `<img src="${src}" alt="Media preview" class="img-fluid rounded" style="max-width:120px; max-height:120px; width:auto; height:auto; object-fit:cover;" data-modpress-media-preview-image="true" />`)
                    .join('');
            }

            if (previewContainer) {
                previewContainer.classList.toggle('d-none', urlValues.length === 0);
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