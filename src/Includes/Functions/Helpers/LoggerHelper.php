<?php
/**
 * This file contains the core logging & debugging functions for the plugin.
 * 
 * 
 * 
 * @package    ModPress
 * @subpackage ModPress/includes
 * @since      1.0.0
 * @author     MrTrilB <
 */
namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Settings\Settings;

class LoggerHelper {

    /**
     * Writes a log message to the debug log if WP_DEBUG is enabled or if plugin logging is enabled.
     *
     * @param mixed $log The log message to write. Can be a string, array, or object.
     * @param array $settings Optional settings for logging. Currently unused.
     * @return void
     */
    public static function write_log( $log, array $settings = [] ): void {

        $debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;

        $plugin_logging = (bool) Settings::get( 'debug_logging', false );

        if ( ! $debug_enabled && ! $plugin_logging ) {
            return;
        }

        error_log( is_array( $log ) || is_object( $log ) ? print_r( $log, true ) : (string) $log );
    }
    /**
     * Writes a log message to the browser console if WP_DEBUG is enabled or if plugin logging is enabled.
     *
     * @param mixed $log The log message to write. Can be a string, array, or object.
     * @param array $settings Optional settings for logging. Currently unused.
     * @return void
     */
    public static function write_console( $log, array $settings = [] ): void {

        $debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;

        $plugin_logging = (bool) Settings::get( 'debug_logging', false );

        $plugin_console_logging = (bool) Settings::get( 'console_logging', false );
        
        if ( ! $debug_enabled && ! $plugin_logging && ! $plugin_console_logging ) {
            return;
        }
        echo '<script>console.log(' . json_encode( $log ) . ');</script>';
    }
}