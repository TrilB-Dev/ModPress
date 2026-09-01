<?php
/**
 * Language internationalization (i18n) for the TinyMCE plugin.
 * @package ModPress
 * @subpackage Plugins\TinyMCE\Includes
 * @since 1.0.0
 * 
 */
namespace ModPress\Includes\Plugins\TinyMCE\Includes\Core;

class I18n {
    /**
     * Loads the plugin's text domain for translation.
     */
    public static function load_textdomain(): void {
        load_plugin_textdomain(
            'modpress',
            false,
            dirname( plugin_basename( MODPRESS_FILE ) ) . '/src/includes/Plugins/TinyMCE/Language/'
        );
    }
}