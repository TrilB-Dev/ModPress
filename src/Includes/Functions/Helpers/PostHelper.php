<?php
/**
 * Post identity and ModPress post-type helpers.
 *
 * @package ModPress
 * @subpackage Includes\Functions\Helpers
 * @since 1.0.0
 */

namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Core\PostType;
use ModPress\Includes\Functions\Helpers\QueryHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


final class PostHelper {
	/**
	 * Get the current post object.
	 *
	 * @return \WP_Post|null The current post object or null if not available.
	 * @since 1.0.0
	 */
	public static function current(): ?\WP_Post {
		$query = QueryHelper::current();
		return $query instanceof \WP_Query && $query->post instanceof \WP_Post ? $query->post : null;
	}
	/**
	 * Get the ID of the current post.
	 *
	 * @return int The ID of the current post or 0 if not available.
	 * @since 1.0.0
	 */
	public static function current_id(): int {
		return self::current() ? absint( self::current()->ID ) : 0;
	}
	/**
	 * Get the post type of the current post.
	 *
	 * @return string The post type of the current post or an empty string if not available.
	 * @since 1.0.0
	 */
	public static function current_type(): string {
		return self::current() ? (string) self::current()->post_type : '';
	}

	/**
	 * Get a post object by ID or WP_Post instance.
	 *
	 * @param int|\WP_Post|null $post The post ID, WP_Post instance, or null for the current post.
	 * @return \WP_Post|null The post object or null if not found.
	 * @since 1.0.0
	 */
	public static function get( $post = null ): ?\WP_Post {
		if ( $post instanceof \WP_Post ) {
			return $post;
		}

		if ( is_numeric( $post ) && absint( $post ) > 0 ) {
			$post = get_post( absint( $post ) );
		} elseif ( null === $post ) {
			$post = get_post();
		}

		return $post instanceof \WP_Post ? $post : null;
	}

	/**
	 * Get the ID of a post.
	 *
	 * @param int|\WP_Post|null $post The post ID, WP_Post instance, or null for the current post.
	 * @return int The post ID or 0 if not found.
	 * @since 1.0.0
	 */
	public static function id( $post = null ): int {
		$post = self::get( $post );
		return $post ? absint( $post->ID ) : 0;
	}

	/**
	 * Check if a post is of a specific post type.
	 *
	 * @param int|\WP_Post|null $post The post ID, WP_Post instance, or null for the current post.
	 * @param string $post_type The post type to check.
	 * @return bool True if the post is of the specified post type, false otherwise.
	 * @since 1.0.0
	 */
	public static function is_type( $post, string $post_type ): bool {
		$post = self::get( $post );
		return null !== $post && $post->post_type === $post_type;
	}

	/**
	 * Check if a post is a wiki post.
	 *
	 * @param int|\WP_Post|null $post The post ID, WP_Post instance, or null for the current post.
	 * @return bool True if the post is a wiki post, false otherwise.
	 * @since 1.0.0
	 */
	public static function is_wiki( $post ): bool {
		return self::is_type( $post, PostType::WIKI );
	}

	/**
	 * Check if a post is a wiki page.
	 *
	 * @param int|\WP_Post|null $post The post ID, WP_Post instance, or null for the current post.
	 * @return bool True if the post is a wiki page, false otherwise.
	 * @since 1.0.0
	 */
	public static function is_wiki_page( $post ): bool {
		return self::is_type( $post, PostType::PAGE );
	}

	/**
	 * Get the permalink of a post.
	 *
	 * @param int|\WP_Post|null $post The post ID, WP_Post instance, or null for the current post.
	 * @return string The permalink of the post or an empty string if not available.
	 * @since 1.0.0
	 */
	public static function permalink( $post = null ): string {
		$post_id = self::id( $post );
		return $post_id > 0 ? (string) get_permalink( $post_id ) : '';
	}
}





