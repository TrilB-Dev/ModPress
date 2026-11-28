<?php
/**
 * ProjectPress integration for ModPress.
 *
 * @package ModPress\Includes\Integrations\ProjectPress
 */
namespace ModPress\Includes\Integrations\ProjectPress;
use ModPress\Includes\Integrations\IntegrationInterface;
use ModPress\Includes\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class ProjectPress implements IntegrationInterface {
	/**
	 * Retrieve the slug of the integration bridge.
	 *
	 * @since 1.0.0
	 * @return string The slug of the integration bridge.
	 */
	public function get_slug(): string {
		return 'projectpress';
	}

	/**
	 * Retrieve the name of the integration bridge.
	 *
	 * @since 1.0.0
	 * @return string The name of the integration bridge.
	 */
	public function get_name(): string {
		return 'ProjectPress';
	}

	/**
	 * Retrieve the host plugin class of the integration bridge.
	 *
	 * @since 1.0.0
	 * @return string The host plugin class.
	 */
	public function get_host_plugin_class(): string {
		return 'ProjectPress';
	}

	/**
	 * Retrieve the setting key for the integration bridge.
	 *
	 * @since 1.0.0
	 * @return string The setting key.
	 */
	public function get_setting_key(): string {
		return 'enable_projectpress_integration';
	}

	/**
	 * Check if the integration bridge is available.
	 *
	 * @since 1.0.0
	 * @return bool True if available, false otherwise.
	 */
	public function is_available(): bool {
		if ( defined( 'PROJECTPRESS_FILE' ) ) {
			return true;
		}
		if ( class_exists( 'ProjectPress\\Plugin' ) || class_exists( $this->get_host_plugin_class() ) ) {
			return true;
		}
		if ( function_exists( 'is_plugin_active' ) ) {
			return is_plugin_active( 'projectpress/projectpress.php' );
		}
		if ( function_exists( 'get_option' ) ) {
			$active_plugins = array_map( 'strtolower', (array) get_option( 'active_plugins', array() ) );
			return in_array( 'projectpress/projectpress.php', $active_plugins, true );
		}
		return false;
	}

	/**
	 * Check if the integration bridge is enabled.
	 *
	 * @since 1.0.0
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		return Settings::get_bool( $this->get_setting_key(), false );
	}

	/**
	 * Enable or disable the integration bridge.
	 *
	 * @since 1.0.0
	 * @param bool $enabled True to enable, false to disable.
	 * @return bool True on success, false on failure.
	 */
	public function set_enabled( bool $enabled ): bool {
		return Settings::set( $this->get_setting_key(), $enabled );
	}

	/**
	 * Initialize the integration bridge.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {}

	/**
	 * Process an action for the integration bridge.
	 *
	 * @since 1.0.0
	 * @param string $action The action to process.
	 * @param array $context The context for the action.
	 * @return mixed The result of the action.
	 */
	public function process( string $action, array $context = array() ) {
		return null;
	}
	public function render_page_content(): void {}
}