<?php
/**
 * TinyMCE Editor Plugin Helper Functions
 *
 * @package ModPress
 * @subpackage Plugins\TinyMCE\Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Plugins\TinyMCE\Includes\Functions\Helpers;

use ModPress\Includes\Plugins\TinyMCE\Includes\Settings\Settings;

final class TinyMCEHelper {
    /**
     * Render a TinyMCE editor.
     *
     * @param string $id The ID of the editor.
     * @param string $name The name of the editor.
     * @param string $label The label for the editor.
     * @param string $value The initial content of the editor.
     * @param int $rows The number of rows for the textarea.
     * @param bool $media_buttons Whether to show media buttons.
     * @param array $options Additional TinyMCE options.
     */
    public static function render( string $id, string $name, string $label, string $value = '', int $rows = 8, bool $media_buttons = false, array $options = [] ): void {
        $editor_id = sanitize_key( $id );
        $rows = max( 1, $rows );
        $plugins = Settings::plugins();
        $content_skin = Settings::content_skin();
        $ui_skin = Settings::ui_skin();
        $locale = function_exists( 'get_user_locale' ) ? get_user_locale() : 'en_GB';
        $language_path = '';
        $language_dir = dirname( MODPRESS_FILE ) . '/src/includes/Plugins/TinyMCE/Assets/tinymce/langs';
        $locale_candidates = [ $locale, str_replace( '-', '_', $locale ), str_replace( '_', '-', $locale ), strtolower( $locale ) ];
        foreach ( glob( $language_dir . '/*.js' ) ?: [] as $candidate_path ) {
            $candidate_locale = pathinfo( $candidate_path, PATHINFO_FILENAME );
            if ( in_array( strtolower( $candidate_locale ), array_map( 'strtolower', $locale_candidates ), true ) ) {
                $language_path = $candidate_path;
                $locale = $candidate_locale;
                break;
            }
        }
        $language_url = file_exists( $language_path )
            ? MODPRESS_URL . 'src/includes/Plugins/TinyMCE/Assets/tinymce/langs/' . basename( $language_path )
            : '';
        $toolbar = [ 'blocks' ];
        $toolbar[] = '|';
        $toolbar[] = 'bold italic underline strikethrough';
        $toolbar[] = '|';
        if ( in_array( 'lists', $plugins, true ) || in_array( 'advlist', $plugins, true ) ) {
            $toolbar[] = 'bullist numlist';
        }
        $toolbar[] = 'blockquote';
        $toolbar[] = '|';
        if ( in_array( 'link', $plugins, true ) ) {
            $toolbar[] = 'link';
        }
        if ( $media_buttons ) {
            $toolbar[] = 'modpressmedia';
        }
        foreach ( [ 'image', 'media', 'table' ] as $button ) {
            if ( in_array( $button, $plugins, true ) ) {
                $toolbar[] = $button;
            }
        }
        $toolbar[] = '|';
        $toolbar[] = 'undo redo removeformat';
        if ( in_array( 'code', $plugins, true ) ) {
            $toolbar[] = 'code';
        }
        $settings = [
            'selector' => '#' . $editor_id,
            'height' => max( 120, $rows * 24 ),
            'menubar' => false,
            'branding' => false,
            'promotion' => false,
            'license_key' => 'gpl',
            'plugins' => implode( ' ', $plugins ),
            'toolbar' => implode( ' ', $toolbar ),
            'base_url' => MODPRESS_URL . 'src/includes/Plugins/TinyMCE/Assets/tinymce',
            'skin' => $ui_skin,
            'skin_url' => MODPRESS_URL . 'src/includes/Plugins/TinyMCE/Assets/tinymce/skins/ui/' . $ui_skin,
            'content_css' => MODPRESS_URL . 'src/includes/Plugins/TinyMCE/Assets/tinymce/skins/content/' . $content_skin . '/content.min.css',
            'media_buttons' => $media_buttons,
        ];
        if ( $language_url ) {
            $settings['language'] = $locale;
            $settings['language_url'] = $language_url;
        }

        $settings = apply_filters( 'modpress_tinymce_settings', array_merge( $settings, $options ), $editor_id );

        printf( '<label class="form-label" for="%1$s">%2$s</label>', esc_attr( $editor_id ), esc_html( $label ) );
        printf( '<textarea class="form-control modpress-tinymce" id="%1$s" name="%2$s" rows="%3$d">%4$s</textarea>', esc_attr( $editor_id ), esc_attr( $name ), $rows, esc_textarea( $value ) );
        printf( '<script type="application/json" class="modpress-tinymce-config" data-editor="%1$s">%2$s</script>', esc_attr( $editor_id ), wp_json_encode( $settings ) );
    }

}