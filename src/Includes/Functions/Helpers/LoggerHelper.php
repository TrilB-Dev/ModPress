<?php
/**
 * This file contains the core logging & debugging functions for the plugin.
 *
 * @package    ModPress
 * @since      1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Analytics\Analytics;
use ModPress\Includes\Settings\Settings;

class LoggerHelper {

	/**
	 * Writes a log message to the debug log if WP_DEBUG is enabled or if plugin logging is enabled.
	 *
	 * @param mixed $log The log message to write. Can be a string, array, or object.
	 * @param array $settings Optional settings for logging. Currently unused.
	 * @return void
	 */
	public static function write_log( $log, array $_settings = array() ): void {
		unset( $_settings );

		$debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;

		$plugin_logging = (bool) Settings::get( 'debug_logging', false );

		if ( ! $debug_enabled && ! $plugin_logging ) {
			return;
		}

		$message = is_array( $log ) || is_object( $log ) ? print_r( $log, true ) : (string) $log;
		Analytics::log_event( 'debug', $message, is_array( $log ) || is_object( $log ) ? (array) $log : array( 'message' => $message ) );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r
		error_log( $message );
	}
	/**
	 * Writes a log message to the browser console if WP_DEBUG is enabled or if plugin logging is enabled.
	 *
	 * @param mixed $log The log message to write. Can be a string, array, or object.
	 * @param array $settings Optional settings for logging. Currently unused.
	 * @return void
	 */
	public static function write_console( $log, array $_settings = array() ): void {
		unset( $_settings );

		$debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;

		$plugin_logging = (bool) Settings::get( 'debug_logging', false );

		$plugin_console_logging = (bool) Settings::get( 'console_logging', false );

		if ( ! $debug_enabled && ! $plugin_logging && ! $plugin_console_logging ) {
			return;
		}
		echo '<script>console.log(' . wp_json_encode( $log ) . ');</script>';
	}
}





