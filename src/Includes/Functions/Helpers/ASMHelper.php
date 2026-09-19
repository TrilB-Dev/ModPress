<?php
/**
 * ModPress Sidebar Admin Menu helper.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASMHelper {
	/**
	 * The filter hook for modifying the admin sidebar menus.
	 *
	 * @var string
	 */
	public const FILTER = 'modpress_admin_sidebar_menus';

	/**
	 * Create a ModPress sidebar menu definition.
	 *
	 * @param string $name Menu label.
	 * @param string $slug Menu page slug.
	 * @param string $icon Font Awesome icon classes.
	 * @param string $parent Existing group slug, or empty for a new group.
	 * @return array<string, mixed>
	 */
	public static function define( string $name, string $slug, string $icon, string $parent = '', string $capability = '' ): array {
		$clean_slug = self::sanitize_slug( $slug );
		$clean_name = trim( (string) $name );

		return array(
			'parent'     => sanitize_key( (string) $parent ),
			'name'       => $clean_name,
			'slug'       => $clean_slug,
			'icon'       => sanitize_text_field( $icon ),
			'capability' => sanitize_key( (string) $capability ),
		);
	}

	/**
	 * Pass sidebar menu definitions through the extension filter.
	 *
	 * @param array<int, array<string, mixed>> $menus Menu definitions.
	 * @return array<int, array<string, mixed>>
	 */
	public static function filter( array $menus ): array {
		$filtered = apply_filters( self::FILTER, $menus );
		return is_array( $filtered ) ? array_values( array_filter( $filtered, 'is_array' ) ) : $menus;
	}
	/**
	 * Retrieve the URL for a sidebar menu item based on its slug.
	 *
	 * @param string $slug The menu slug.
	 * @return string The URL for the menu item.
	 */
	public static function get_url( string $slug ): string {
		return admin_url( 'admin.php?page=' . self::sanitize_slug( $slug ) );
	}
	/**
	 * Sanitize a sidebar menu slug.
	 *
	 * @param string $slug The menu slug.
	 * @return string The sanitized slug.
	 */
	private static function sanitize_slug( string $slug ): string {
		$slug = trim( (string) $slug );
		if ( '' === $slug || preg_match( '/^\d+$/', $slug ) ) {
			return '';
		}

		$parts = explode( '&', $slug, 2 );
		$page  = sanitize_key( $parts[0] );
		if ( '' === $page || preg_match( '/^\d+$/', $page ) ) {
			return '';
		}

		return $page . ( isset( $parts[1] ) && '' !== $parts[1] ? '&' . sanitize_text_field( $parts[1] ) : '' );
	}
}



