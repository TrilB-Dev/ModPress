<?php
/**
 * Tokenized ModPress permalink support.
 *
 * @package ModPress
 */

namespace ModPress\Includes\Functions\Helpers;

use ModPress\Includes\Core\PostType;
use ModPress\Includes\Core\Taxonomy;
use ModPress\Includes\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PermalinkHelper {
	/**
	 * Meta key for overriding the permalink pattern for a specific object.
	 *
	 * @since 1.0.0
	 */
	public const OVERRIDE_META = '_modpress_permalink';
	/**
	 * Get the token definitions for the permalink patterns.
	 *
	 * @since 1.0.0
	 */
	public static function token_definitions(): array {
		return array(
			'%root%'          => __( 'The root ModPress slug.', 'modpress' ),
			'%root_category%' => __( 'The Wiki categories, from parent to child.', 'modpress' ),
			'%root_tags%'     => __( 'The tags assigned to the Wiki container.', 'modpress' ),
			'%wiki%'          => __( 'The Wiki slug.', 'modpress' ),
			'%wiki_category%' => __( 'The Wiki page categories, from parent to child.', 'modpress' ),
			'%wiki_tag%'      => __( 'The tags assigned to the Wiki page.', 'modpress' ),
			'%wiki_page%'     => __( 'The Wiki page slug.', 'modpress' ),
		);
	}

	public static function default_pattern(): string {
		return '%root%/%root_category%/%wiki%/%wiki_category%/%wiki_tag%/%wiki_page%';
	}

	/**
	 * Sanitize a permalink pattern.
	 *
	 * @param string $pattern The pattern to sanitize.
	 * @return string The sanitized pattern.
	 * @since 1.0.0
	 */
	public static function sanitize_pattern( string $pattern ): string {
		$pattern  = trim( (string) $pattern );
		$allowed  = array_keys( self::token_definitions() );
		$segments = array();

		$split_segments = preg_split( '#/+#', trim( $pattern, '/' ) );
		$segments_list  = is_array( $split_segments ) ? $split_segments : array();

		foreach ( $segments_list as $segment ) {
			$segment = trim( $segment );
			if ( '' === $segment ) {
				continue;
			}
			if ( in_array( $segment, $allowed, true ) ) {
				$segments[] = $segment;
				continue;
			}
			$slug = sanitize_title( $segment );
			if ( '' !== $slug ) {
				$segments[] = $slug;
			}
		}

		return implode( '/', $segments );
	}
	/**
	 * Get the permalink pattern for a specific object.
	 *
	 * @param int $object_id The object ID.
	 * @return string The permalink pattern.
	 * @since 1.0.0
	 */
	public static function pattern_for_object( int $object_id = 0 ): string {
		$pattern = '';
		if ( $object_id > 0 ) {
			$pattern = get_post_meta( $object_id, self::OVERRIDE_META, true );
		}

		$resolved_pattern = '' !== $pattern ? $pattern : Settings::get( 'permalink', self::default_pattern() );
		$sanitized        = self::sanitize_pattern( $resolved_pattern );
		return '' !== $sanitized ? $sanitized : self::default_pattern();
	}
	/**
	 * Get the URL for a specific wiki page.
	 *
	 * @param \WP_Post $page The wiki page post object.
	 * @return string The URL of the wiki page.
	 * @since 1.0.0
	 */
	public static function page_url( \WP_Post $page ): string {
		$wiki_id = absint( get_post_meta( $page->ID, '_modpress_wiki_id', true ) );
		$wiki    = null;
		if ( $wiki_id > 0 ) {
			$wiki = get_post( $wiki_id );
		}
		$pattern = self::pattern_for_object( $wiki_id );
		$path    = self::expand( $pattern, $page, $wiki instanceof \WP_Post ? $wiki : null );
		return home_url( user_trailingslashit( trim( $path, '/' ) ) );
	}
	/**
	 * Expand a permalink pattern into a full path for a specific wiki page.
	 *
	 * @param string $pattern The permalink pattern.
	 * @param \WP_Post $page The wiki page post object.
	 * @param \WP_Post|null $wiki The wiki post object, or null if not applicable.
	 * @return string The expanded permalink path.
	 * @since 1.0.0
	 */
	public static function expand( string $pattern, \WP_Post $page, ?\WP_Post $wiki = null ): string {
		$root_slug = sanitize_title( (string) Settings::get( 'root_slug', 'wiki' ) );
		$wiki_name = '';
		if ( $wiki instanceof \WP_Post ) {
			$wiki_post_name = $wiki->post_name;
			if ( '' === $wiki_post_name ) {
				$wiki_post_name = $wiki->post_title;
			}
			$wiki_name = sanitize_title( $wiki_post_name );
		}

		$root_category = '';
		$root_tags     = '';
		if ( $wiki instanceof \WP_Post ) {
			$root_category = self::term_path( Taxonomy::CATEGORY, $wiki->ID );
			$root_tags     = self::term_path( Taxonomy::TAG, $wiki->ID );
		}

		$page_title = $page->post_name;
		if ( '' === $page_title ) {
			$page_title = $page->post_title;
		}
		$wiki_page = sanitize_title( $page_title );
		$values    = array(
			'%root%'          => $root_slug,
			'%root_category%' => $root_category,
			'%root_tags%'     => $root_tags,
			'%wiki%'          => $wiki_name,
			'%wiki_category%' => self::term_path( Taxonomy::CATEGORY, $page->ID ),
			'%wiki_tag%'      => self::term_path( Taxonomy::TAG, $page->ID ),
			'%wiki_page%'     => $wiki_page,
		);

		$normalized = self::sanitize_pattern( $pattern );
		$path       = strtr( $normalized, $values );
		if ( ! str_contains( $normalized, '%wiki_page%' ) ) {
			$path .= '/' . $values['%wiki_page%'];
		}
		return trim( preg_replace( '#/+#', '/', trim( $path, '/' ) ), '/' );
	}
	/**
	 * Register the rewrite rule for the plugin's custom permalinks.
	 *
	 * @since 1.0.0
	 */
	public static function rewrite_rule(): void {
		add_rewrite_rule( '^(.+?)/?$', 'index.php?modpress_path=$matches[1]', 'top' );
		add_filter(
			'query_vars',
			static function ( array $vars ): array {
				$vars[] = 'modpress_path';
				return $vars;
			}
		);
		add_filter( 'request', array( self::class, 'resolve_request' ) );
	}
	/**
	 * Resolve the requested path to the corresponding WordPress query variables.
	 *
	 * @param array $vars The query variables.
	 * @return array The modified query variables.
	 * @since 1.0.0
	 */
	public static function resolve_request( array $vars ): array {
		$requested_path = isset( $vars['modpress_path'] ) ? trim( urldecode( (string) $vars['modpress_path'] ), '/' ) : '';
		if ( '' === $requested_path ) {
			return $vars;
		}

		$pages = get_posts(
			array(
				'post_type'        => PostType::MODPRESS,
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'suppress_filters' => false,
			)
		);
		foreach ( $pages as $page ) {
			if ( self::page_url_path( $page ) === $requested_path ) {
				return array( 'p' => $page->ID );
			}
		}

		return $vars;
	}
	/**
	 * Filter the permalink for a wiki page.
	 *
	 * @param string $link The original permalink.
	 * @param \WP_Post $post The post object.
	 * @return string The filtered permalink.
	 * @since 1.0.0
	 */
	public static function filter_page_permalink( string $link, \WP_Post $post ): string {
		return $post->post_type === PostType::MODPRESS ? self::page_url( $post ) : $link;
	}
	/**
	 * Get the URL path for a specific wiki page.
	 *
	 * @param \WP_Post $page The wiki page post object.
	 * @return string The URL path of the wiki page.
	 * @since 1.0.0
	 */
	private static function page_url_path( \WP_Post $page ): string {
		$wiki_id = absint( get_post_meta( $page->ID, '_modpress_wiki_id', true ) );
		$wiki    = null;
		if ( $wiki_id > 0 ) {
			$wiki = get_post( $wiki_id );
		}
		return self::expand( self::pattern_for_object( $wiki_id ), $page, $wiki instanceof \WP_Post ? $wiki : null );
	}
	/**
	 * Get the URL path for a specific taxonomy term associated with a post.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @param int $post_id The post ID.
	 * @return string The URL path of the taxonomy term.
	 * @since 1.0.0
	 */
	private static function term_path( string $taxonomy, int $post_id ): string {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return '';
		}

		$ordered = array();
		foreach ( $terms as $term ) {
			$ancestors = is_taxonomy_hierarchical( $taxonomy ) ? array_reverse( get_ancestors( $term->term_id, $taxonomy, 'taxonomy' ) ) : array();
			foreach ( array_merge( $ancestors, array( $term->term_id ) ) as $term_id ) {
				$ancestor = get_term( $term_id, $taxonomy );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$slug = $ancestor->slug;
					if ( '' === $slug ) {
						$slug = $ancestor->name;
					}
					$ordered[ $ancestor->term_id ] = sanitize_title( $slug );
				}
			}
		}

		return implode( '/', array_filter( $ordered ) );
	}
}









