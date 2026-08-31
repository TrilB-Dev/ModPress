(() => {
    'use strict';

    const insertMedia = (editor) => {
        if (!window.wp?.media) {
            return;
        }

        var frame = window.wp.media({
            title: window.modpressTinyMCE?.mediaTitle || '',
            button: { text: window.modpressTinyMCE?.mediaButton || '' },
            multiple: false
        });

        frame.on('select', () => {
            const attachment = frame.state().get('selection').first().toJSON();
            const url = attachment.url || '';
            if (url) {
                editor.insertContent('<img src="' + editor.dom.encode(url) + '" alt="' + editor.dom.encode(attachment.alt || attachment.title || '') + '">');
            }
        });

        frame.open();
    };

    const initialize = () => {
        if (!window.tinymce) {
            return;
        }

        document.querySelectorAll('.modpress-tinymce-config').forEach((node) => {
            let settings;
            try {
                settings = JSON.parse(node.textContent || '{}');
            } catch {
                return;
            }

            settings.setup = (editor) => {
                if (settings.media_buttons) {
                    editor.ui.registry.addButton('modpressmedia', {
                        icon: 'image',
                        tooltip: window.modpressTinyMCE?.mediaTooltip || '',
                        onAction: () => insertMedia(editor)
                    });
                }
            };

            window.tinymce.init(settings);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
})();