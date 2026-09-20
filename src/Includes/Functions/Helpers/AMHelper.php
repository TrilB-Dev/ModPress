<?php
/**
 * WordPress Admin Menu Helper class for ModPress plugin.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */

namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AMHelper {
	/**
	 * The filter hook for modifying the admin menus.
	 *
	 * @var string
	 */
	public const FILTER = 'modpress_admin_menus';

	/**
	 * Create a WordPress admin menu definition.
	 *
	 * @param string $name Menu label.
	 * @param string $slug Menu slug.
	 * @param string $icon Dashicon or icon URL.
	 * @param string $parent Parent menu slug, or empty for a top-level menu.
	 * @return array<string, mixed>
	 * @since 1.0.0
	 */
	public static function define( string $name, string $slug, string $icon = 'dashicons-admin-generic', string $parent = '' ): array {
		$clean_slug = self::normalize_slug( $slug );
		$clean_name = trim( (string) $name );

		return array(
			'parent' => sanitize_key( (string) $parent ),
			'name'   => $clean_name,
			'slug'   => $clean_slug,
			'icon'   => sanitize_text_field( $icon ),
		);
	}

	/**
	 * Normalize a menu slug and reject numeric-only values that would turn into page=0 links.
	 *
	 * @param string $slug Slug candidate.
	 * @return string Normalized slug or empty string when invalid.
	 * @since 1.0.0
	 */
	private static function normalize_slug( string $slug ): string {
		$slug = trim( (string) $slug );
		if ( '' === $slug || preg_match( '/^\d+$/', $slug ) ) {
			return '';
		}

		if ( false !== strpos( $slug, '&' ) ) {
			$base = trim( (string) strtok( $slug, '&' ) );
			if ( '' === $base || preg_match( '/^\d+$/', $base ) ) {
				return '';
			}
			return $base . substr( $slug, strlen( $base ) );
		}

		return sanitize_key( $slug );
	}

	/**
	 * Pass WordPress admin menu definitions through the extension filter.
	 *
	 * @param array<int, array<string, mixed>> $menus Menu definitions.
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public static function filter( array $menus ): array {
		$filtered = apply_filters( self::FILTER, $menus );
		return is_array( $filtered ) ? array_values( array_filter( $filtered, 'is_array' ) ) : $menus;
	}

	/**
	 * Get the admin menu page URL for a given slug.
	 *
	 * @param string $slug The slug of the admin menu page.
	 * @return string The URL of the admin menu page.
	 * @since 1.0.0
	 */
	public static function get_admin_menu_page_url( string $slug ): string {
		return admin_url( 'admin.php?page=' . $slug );
	}
}