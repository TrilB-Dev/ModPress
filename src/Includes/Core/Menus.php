<?php
/**
 * Core WordPress menu registry for ModPress.
 *
 * @package ModPress
 * @subpackage Includes\Core
 * @since 1.0.0
 */
namespace ModPress\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Menus {
	/**
	 * Shared registry singleton.
	 *
	 * @var self|null
	 * @since 1.0.0
	 */
	private static ?self $instance = null;

	/**
	 * Registered menu locations keyed by menu location slug.
	 *
	 * @var array<string, string>
	 * @since 1.0.0
	 */
	private static array $registered = array();

	/**
	 * Instance-local menu locations keyed by slug.
	 *
	 * @var array<string, string>
	 * @since 1.0.0
	 */
	private array $locations = array();

	/**
	 * Get the shared registry singleton.
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function get_instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Create a new menu registry and optionally hydrate it from a location map.
	 *
	 * @param array<string, string> $locations Default menu locations.
	 * @return self
	 * @since 1.0.0
	 */
	public static function create( array $locations = array() ): self {
		$registry = new self();
		foreach ( $locations as $slug => $label ) {
			$registry->register_location( (string) $slug, (string) $label, true );
		}

		return $registry;
	}

	/**
	 * Create a menu registry with optional pre-registered locations.
	 *
	 * @param array<string, string> $locations Default menu locations.
	 * @since 1.0.0
	 */
	public function __construct( array $locations = array() ) {
		foreach ( $locations as $slug => $label ) {
			$this->register_location( (string) $slug, (string) $label, true );
		}
	}

	/**
	 * Build a normalized menu definition array.
	 *
	 * @param string $location Menu location slug.
	 * @param string $label Menu label.
	 * @return array<string, string>
	 * @since 1.0.0
	 */
	public static function define( string $location, string $label = '' ): array {
		$slug = self::normalize_location_slug_static( $location );
		return array(
			'location' => $slug,
			'label'    => '' !== $label ? $label : ucfirst( str_replace( '-', ' ', $slug ) ),
		);
	}

	/**
	 * Create a menu location from a UI or database payload.
	 *
	 * @param array<string, mixed> $data UI payload.
	 * @return bool
	 * @since 1.0.0
	 */
	public static function dynamically_create( array $data = array() ): bool {
		$location = (string) ( $data['location'] ?? $data['slug'] ?? $data['name'] ?? '' );
		$label    = (string) ( $data['label'] ?? '' );
		if ( '' === $location ) {
			return false;
		}

		$registry = self::get_instance();
		if ( $registry->has_location( $location ) ) {
			return false;
		}

		$registry->register_location( $location, $label, true );
		self::$registered[ $registry->normalize_location_slug( $location ) ] = $registry->locations[ $registry->normalize_location_slug( $location ) ];
		return true;
	}

	/**
	 * Register multiple menu locations from a definition map.
	 *
	 * @param array<string, string> $menus Menu location definitions.
	 * @return void
	 * @since 1.0.0
	 */
	public static function register_menus( array $menus = array() ): void {
		foreach ( $menus as $slug => $label ) {
			self::get_instance()->register_location( (string) $slug, (string) $label, true );
		}
	}

	/**
	 * Register a WordPress theme menu location.
	 *
	 * @param string $location Menu location slug.
	 * @param string $label Human-readable label.
	 * @param bool   $overwrite Whether to replace an existing location.
	 * @return self
	 * @since 1.0.0
	 */
	public function register_location( string $location, string $label, bool $overwrite = false ): self {
		$slug = $this->normalize_location_slug( $location );
		if ( '' === $slug ) {
			return $this;
		}

		if ( ! $overwrite && isset( $this->locations[ $slug ] ) ) {
			return $this;
		}

		$this->locations[ $slug ] = '' !== $label ? $label : ucfirst( str_replace( '-', ' ', $slug ) );
		self::$registered[ $slug ] = $this->locations[ $slug ];

		if ( function_exists( 'register_nav_menus' ) ) {
			register_nav_menus( array( $slug => $this->locations[ $slug ] ) );
		}

		return $this;
	}

	/**
	 * Get all registered menu locations.
	 *
	 * @return array<string, string>
	 * @since 1.0.0
	 */
	public function get_locations(): array {
		return $this->locations;
	}

	/**
	 * Get all globally registered menu locations.
	 *
	 * @return array<string, string>
	 * @since 1.0.0
	 */
	public static function definitions(): array {
		return self::$registered;
	}

	/**
	 * Check whether a menu location has already been registered.
	 *
	 * @param string $location Menu location slug.
	 * @return bool
	 * @since 1.0.0
	 */
	public function has_location( string $location ): bool {
		$slug = $this->normalize_location_slug( $location );
		return isset( $this->locations[ $slug ] ) || isset( self::$registered[ $slug ] );
	}

	/**
	 * Remove a registered menu location.
	 *
	 * @param string $location Menu location slug.
	 * @return self
	 * @since 1.0.0
	 */
	public function remove_location( string $location ): self {
		$slug = $this->normalize_location_slug( $location );
		if ( '' !== $slug ) {
			unset( $this->locations[ $slug ] );
			unset( self::$registered[ $slug ] );
		}

		return $this;
	}

	/**
	 * Normalize a location slug to the WordPress standard.
	 *
	 * @param string $location Raw location slug.
	 * @return string
	 * @since 1.0.0
	 */
	private function normalize_location_slug( string $location ): string {
		return self::normalize_location_slug_static( $location );
	}

	/**
	 * Normalize a location slug to the WordPress standard.
	 *
	 * @param string $location Raw location slug.
	 * @return string
	 * @since 1.0.0
	 */
	private static function normalize_location_slug_static( string $location ): string {
		$slug = trim( (string) $location );
		if ( '' === $slug ) {
			return '';
		}

		if ( function_exists( 'sanitize_key' ) ) {
			$slug = sanitize_key( $slug );
		} else {
			$slug = strtolower( $slug );
			$slug = preg_replace( '/[^a-z0-9_-]+/', '', $slug );
			$slug = (string) $slug;
		}

		return $slug;
	}
}
