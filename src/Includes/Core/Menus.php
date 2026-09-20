<?php
/**
 * Core WordPress menu registry for ModPress.
 *
 * @package ModPress\Includes\Core
 */
namespace ModPress\Includes\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Menus {
	/**
	 * Registered menu locations keyed by menu location slug.
	 *
	 * @var array<string, string>
	 */
	private array $locations = array();

	/**
	 * Create a menu registry with optional pre-registered locations.
	 *
	 * @param array<string, string> $locations Default menu locations.
	 */
	public function __construct( array $locations = array() ) {
		foreach ( $locations as $slug => $label ) {
			$this->register_location( (string) $slug, (string) $label, true );
		}
	}

	/**
	 * Register a WordPress theme menu location.
	 *
	 * @param string $location Menu location slug.
	 * @param string $label Human-readable label.
	 * @param bool   $overwrite Whether to replace an existing location.
	 * @return self
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

		if ( function_exists( 'register_nav_menus' ) ) {
			register_nav_menus( array( $slug => $this->locations[ $slug ] ) );
		}

		return $this;
	}

	/**
	 * Get all registered menu locations.
	 *
	 * @return array<string, string>
	 */
	public function get_locations(): array {
		return $this->locations;
	}

	/**
	 * Check whether a menu location has already been registered.
	 *
	 * @param string $location Menu location slug.
	 * @return bool
	 */
	public function has_location( string $location ): bool {
		return isset( $this->locations[ $this->normalize_location_slug( $location ) ] );
	}

	/**
	 * Remove a registered menu location.
	 *
	 * @param string $location Menu location slug.
	 * @return self
	 */
	public function remove_location( string $location ): self {
		$slug = $this->normalize_location_slug( $location );
		if ( '' !== $slug ) {
			unset( $this->locations[ $slug ] );
		}

		return $this;
	}

	/**
	 * Normalize a location slug to the WordPress standard.
	 *
	 * @param string $location Raw location slug.
	 * @return string
	 */
	private function normalize_location_slug( string $location ): string {
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
