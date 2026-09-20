<?php
/**
 * Backward-compatible query helper name.
 *
 * @package ModPress\Includes\Core\WP
 * @since 1.0.0
 */
namespace ModPress\Includes\Core\WP;

use ModPress\Includes\Functions\Helpers\QueryHelper;
use ModPress\Includes\Functions\Helpers\RequestHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preserve the original WPQuery API while query behavior lives in QueryHelper.
 *
 * @deprecated Use QueryHelper and PostHelper instead.
 */
final class WPQuery {
	/**
	 * Get the current WP_Query instance.
	 *
	 * @return \WP_Query|null
	 */
	public static function get_current_query(): ?\WP_Query {
		return QueryHelper::current();
	}
	/**
	 * Get the current WP_Post instance.
	 *
	 * @return \WP_Post|null
	 */
	public static function get_current_post(): ?\WP_Post {
		$query = QueryHelper::current();
		return $query instanceof \WP_Query && $query->post instanceof \WP_Post ? $query->post : null;
	}

	/**
	 * Get the ID of the current WP_Post instance.
	 *
	 * @return int|null
	 */
	public static function get_current_post_id(): ?int {
		$post = self::get_current_post();
		return $post instanceof \WP_Post ? (int) $post->ID : null;
	}

	/**
	 * Get the post type of the current WP_Post instance.
	 *
	 * @return string|null
	 */
	public static function get_current_post_type(): ?string {
		$post = self::get_current_post();
		return $post instanceof \WP_Post ? (string) $post->post_type : null;
	}

	/**
	 * Check if the current post is of a specific post type.
	 *
	 * @param string|array $post_type Post type or array of post types to check against.
	 * @return bool
	 */
	public static function is_post_type( $post_type ): bool {
		$current_type = self::get_current_post_type();
		return null !== $current_type && in_array( $current_type, (array) $post_type, true );
	}

	/**
	 * Retrieve a request parameter with optional type casting.
	 *
	 * @param string $key The request parameter key.
	 * @param mixed  $default The default value if the parameter is not set.
	 * @param string $type The expected type ('key', 'text', 'int', 'raw', 'array').
	 * @return mixed
	 */
	public static function request( string $key, $default = null, string $type = 'text' ) {
		if ( 'raw' === $type ) {
			return RequestHelper::get( $key, $default );
		}
		if ( 'array' === $type ) {
			return RequestHelper::array( $_GET, $key, is_array( $default ) ? $default : array() );
		}
		if ( 'int' === $type ) {
			return RequestHelper::get_integer( $key, is_numeric( $default ) ? (int) $default : 0 );
		}
		if ( 'key' === $type ) {
			return RequestHelper::get_key( $key, is_scalar( $default ) ? (string) $default : '' );
		}
		if ( 'text' === $type ) {
			return RequestHelper::get_text( $key, is_scalar( $default ) ? (string) $default : '' );
		}
		throw new \InvalidArgumentException( 'Request type must be key, text, int, raw, or array.' );
	}

	/**
	 * Retrieve posts based on query arguments.
	 *
	 * @param array $args Query arguments.
	 * @return \WP_Query
	 */
	public static function posts( array $args = array() ): \WP_Query {
		return QueryHelper::posts( $args );
	}
}




