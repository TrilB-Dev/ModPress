<?php
/**
 * This file manages the internationalization functionality of the plugin.
 * 
 * 
 * 
 * @package ModPress\Includes\Plugins\FontAwesome\Includes
 * @since 1.0.0
 */

namespace ModPress\Includes\Plugins\FontAwesome\Includes\Core;

final class I18n {
    public static function load_textdomain(): void {
        load_plugin_textdomain(
            'modpress',
            false,
            dirname( plugin_basename( MODPRESS_FILE ) ) . '/src/includes/Plugins/FontAwesome/Language/'
        );
    }
}