<?php
/**
 * WordPress query helpers for ModPress and extensions.
 *
 * @package ModPress\Includes\Functions\Helpers
 * @since 1.0.0
 */
namespace ModPress\Includes\Functions\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class QueryHelper {
	/**
	 * Get the current WordPress query.
	 *
	 * @return \WP_Query|null The current query or null if not available.
	 * @since 1.0.0
	 */
	public static function current(): ?\WP_Query {
		global $wp_query;
		return isset( $wp_query ) && $wp_query instanceof \WP_Query ? $wp_query : null;
	}

	/**
	 * Get posts based on the specified query arguments.
	 *
	 * @param array $args The query arguments.
	 * @return \WP_Query The resulting WP_Query instance.
	 * @since 1.0.0
	 */
	public static function posts( array $args = array() ): \WP_Query {
		return new \WP_Query( $args );
	}
}





