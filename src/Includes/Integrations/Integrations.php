<?php
/**
 * Integration access point and bridge registry.
 *
 * @package ModPress\Includes\Integrations
 * @since 1.0.0
 */
namespace ModPress\Includes\Integrations;

use ModPress\Includes\Integrations\AccessPress\AccessPress;
use ModPress\Includes\Integrations\ProjectPress\ProjectPress;
use ModPress\Includes\Integrations\WikiPress\WikiPress;
use ModPress\Includes\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Integrations {
	/**
	 * The singleton instance of the Integrations class.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * The registered integration bridges.
	 *
	 * @var array<string, IntegrationInterface>
	 * @since 1.0.0
	 */
	private array $bridges = array();
	/**
	 * Whether the default bridges have been registered.
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	private bool $registered = false;

	/**
	 * Whether the integrations have been initialized.
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	private bool $initialized = false;
	/**
	 * The constructor is private to enforce the singleton pattern.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}
	/**
	 * Retrieve the singleton instance of the Integrations class.
	 *
	 * @since 1.0.0
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	/**
	 * Register the default integration bridges.
	 *
	 * @since 1.0.0
	 */
	public function register_default_bridges(): void {
		if ( $this->registered ) {
			return;
		}

		$this->registered = true;
		$this->register_bridge( new WikiPress() );
		$this->register_bridge( new ProjectPress() );
		$this->register_bridge( new AccessPress() );
	}

	/**
	 * Register a single integration bridge.
	 *
	 * @since 1.0.0
	 * @param IntegrationInterface $bridge The integration bridge to register.
	 */
	public function register_bridge( IntegrationInterface $bridge ): void {
		$slug = trim( $bridge->get_slug() );
		if ( '' === $slug ) {
			return;
		}

		$this->bridges[ $slug ] = $bridge;
	}

	/**
	 * Retrieve a registered integration bridge by its slug.
	 *
	 * @since 1.0.0
	 * @param string $slug The slug of the integration bridge.
	 * @return IntegrationInterface|null The integration bridge or null if not found.
	 */
	public function get_bridge( string $slug ): ?IntegrationInterface {
		return $this->bridges[ $slug ] ?? null;
	}

	/**
	 * Retrieve all registered integration bridges.
	 *
	 * @since 1.0.0
	 * @return array<string, IntegrationInterface> The registered integration bridges.
	 */
	public function get_bridges(): array {
		$this->register_default_bridges();
		return $this->bridges;
	}

	/**#
	 * Retrieve all available integration definitions.
	 *
	 * @since 1.0.0
	 * @return array<int, array<string, mixed>>
	 */
	public function get_available_definitions(): array {
		$definitions = array();

		foreach ( $this->get_bridges() as $bridge ) {
			if ( ! $bridge->is_available() ) {
				continue;
			}

			$definitions[] = array(
				'slug'        => $bridge->get_slug(),
				'name'        => $bridge->get_name(),
				'setting_key' => $bridge->get_setting_key(),
				'enabled'     => $bridge->is_enabled(),
			);
		}

		return $definitions;
	}
	/**
	 * Check if a specific integration bridge is enabled.
	 *
	 * @since 1.0.0
	 * @param string $slug The slug of the integration bridge.
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled( string $slug ): bool {
		if ( ! Settings::get_bool( 'enable_integrations', false ) ) {
			return false;
		}

		$bridge = $this->get_bridge( $slug );
		return $bridge ? $bridge->is_enabled() : false;
	}

	/**
	 * Enable or disable a specific integration bridge.
	 *
	 * @since 1.0.0
	 * @param string $slug The slug of the integration bridge.
	 * @param bool $enabled True to enable, false to disable.
	 * @return bool True on success, false on failure.
	 */
	public function set_enabled( string $slug, bool $enabled ): bool {
		$bridge = $this->get_bridge( $slug );
		return $bridge ? $bridge->set_enabled( $enabled ) : false;
	}
	/**
	 * Process an action for a specific integration bridge.
	 *
	 * @since 1.0.0
	 * @param string $slug The slug of the integration bridge.
	 * @param string $action The action to process.
	 * @param array $context The context for the action.
	 * @return mixed|null The result of the action or null if not available or not enabled.
	 */
	public function process( string $slug, string $action, array $context = array() ) {
		$bridge = $this->get_bridge( $slug );
		if ( ! $bridge || ! $bridge->is_available() || ! $this->is_enabled( $slug ) ) {
			return null;
		}

		return $bridge->process( $action, $context );
	}
	/**
	 * Initialize all available and enabled integration bridges.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		$this->register_default_bridges();
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;
		foreach ( $this->bridges as $bridge ) {
			if ( $bridge->is_available() && $this->is_enabled( $bridge->get_slug() ) ) {
				$bridge->init();
			}
		}
	}
}
