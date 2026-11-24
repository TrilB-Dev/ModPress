<?php
/**
 * Contract for third-party plugin integrations.
 *
 * @package ModPress\Includes\Integrations
 * @since 1.0.0
 */
namespace ModPress\Includes\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface IntegrationInterface {
	/**
	 * Retrieve the slug of the integration bridge.
	 *
	 * @since 1.0.0
	 * @return string The slug of the integration bridge.
	 */
	public function get_slug(): string;

	/**
	 * Retrieve the name of the integration bridge.
	 *
	 * @since 1.0.0
	 * @return string The name of the integration bridge.
	 */
	public function get_name(): string;

	/**
	 * Retrieve the host plugin class of the integration bridge.
	 *
	 * @since 1.0.0
	 * @return string The host plugin class.
	 */
	public function get_host_plugin_class(): string;

	/**
	 * Retrieve the setting key for the integration bridge.
	 *
	 * @since 1.0.0
	 * @return string The setting key.
	 */
	public function get_setting_key(): string;

	/**
	 * Check if the integration bridge is available.
	 *
	 * @since 1.0.0
	 * @return bool True if available, false otherwise.
	 */
	public function is_available(): bool;

	/**
	 * Check if the integration bridge is enabled.
	 *
	 * @since 1.0.0
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled(): bool;

	/**
	 * Enable or disable the integration bridge.
	 *
	 * @since 1.0.0
	 * @param bool $enabled True to enable, false to disable.
	 * @return bool True on success, false on failure.
	 */
	public function set_enabled( bool $enabled ): bool;

	/**
	 * Initialize the integration bridge.
	 *
	 * @since 1.0.0
	 */
	public function init(): void;

	/**
	 * Process an action for the integration bridge.
	 *
	 * @since 1.0.0
	 * @param string $action The action to process.
	 * @param array $context The context for the action.
	 * @return mixed The result of the action.
	 */
	public function process( string $action, array $context = array() );
}
