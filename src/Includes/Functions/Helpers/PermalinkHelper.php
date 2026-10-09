<?php
/**
 * Tokenized ModPress permalink support.
 *
 * @package ModPress
 */

namespace ModPress\Includes\Functions\Helpers;

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
	 * Resolve a reusable alias map for a plugin-specific permalink scheme.
	 *
	 * @param array<string, string> $aliases Alias pairs keyed by the legacy token and valued by the canonical token.
	 * @return array<string, string>
	 */
	public static function normalize_aliases( array $aliases ): array {
		$normalized = array();
		foreach ( $aliases as $alias => $canonical ) {
			if ( is_string( $alias ) && is_string( $canonical ) ) {
				$normalized[ trim( $alias ) ] = trim( $canonical );
			}
		}

		return $normalized;
	}

	/**
	 * Sanitize a permalink pattern while preserving plugin-defined token placeholders.
	 *
	 * @param string                $pattern The pattern to sanitize.
	 * @param array<string, string> $aliases Optional alias map such as [ '%wiki%' => '%object%' ].
	 * @return string The canonicalized pattern.
	 */
	public static function sanitize_pattern( string $pattern, array $aliases = array() ): string {
		$pattern = trim( (string) $pattern );
		if ( '' === $pattern ) {
			return '';
		}

		$normalized = strtr( $pattern, self::normalize_aliases( $aliases ) );
		$segments   = array();
		$parts      = preg_split( '#/+#', trim( $normalized, '/' ) );

		foreach ( (array) $parts as $segment ) {
			$segment = trim( (string) $segment );
			if ( '' === $segment ) {
				continue;
			}

			if ( self::is_token( $segment ) ) {
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
	 * @param int    $object_id The object ID.
	 * @param string $fallback  Optional fallback pattern provided by the plugin.
	 * @return string The permalink pattern.
	 * @since 1.0.0
	 */
	public static function pattern_for_object( int $object_id = 0, string $fallback = '' ): string {
		$pattern = '';
		if ( $object_id > 0 ) {
			$pattern = get_post_meta( $object_id, self::OVERRIDE_META, true );
		}

		$resolved_pattern = '' !== $pattern ? $pattern : Settings::get( 'permalink', $fallback );
		$sanitized        = self::sanitize_pattern( (string) $resolved_pattern );
		return '' !== $sanitized ? $sanitized : $fallback;
	}
	/**
	 * Get the URL for a specific page.
	 *
	 * @param \WP_Post $page The page post object.
	 * @return string The URL of the page.
	 * @since 1.0.0
	 */
	public static function page_url( \WP_Post $page ): string {
		$pattern = self::pattern_for_object( (int) $page->ID );
		$path    = self::expand( $pattern, $page );
		return home_url( user_trailingslashit( trim( $path, '/' ) ) );
	}
	/**
	 * Expand a permalink pattern into a full path for a specific page.
	 *
	 * @param string $pattern The permalink pattern.
	 * @param \WP_Post $page The page post object.
	 * @return string The expanded permalink path.
	 * @since 1.0.0
	 */
	public static function expand( string $pattern, \WP_Post $page ): string {
		$root_slug = sanitize_title( (string) Settings::get( 'root_slug', 'catalogue' ) );
		$object_name = '';
		if ( ! empty( $page->post_name ) ) {
			$object_name = sanitize_title( $page->post_name );
		} elseif ( ! empty( $page->post_title ) ) {
			$object_name = sanitize_title( $page->post_title );
		}

		$mod_type   = self::term_path_for_post( $page, true );
		$mod_groups = self::term_path_for_post( $page, true );
		$mod_tags   = self::term_path_for_post( $page, false );
		$mod_post   = $object_name;
		$values     = array(
			'%mod_root%'   => $root_slug,
			'%mod_type%'   => $mod_type,
			'%mod_tags%'   => $mod_tags,
			'%mod_groups%' => $mod_groups,
			'%mod_post%'   => $mod_post,
		);

		$normalized = self::sanitize_pattern( $pattern );
		$path       = strtr( $normalized, $values );
		if ( '' === trim( $path ) ) {
			$path = $object_slug;
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

		$post_types = class_exists( '\ModPress\Includes\Core\PostType' ) ? \ModPress\Includes\Core\PostType::get_post_type_names() : array( 'modpress_page' );
		$pages      = get_posts(
			array(
				'post_type'        => $post_types,
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
		$post_types = class_exists( '\ModPress\Includes\Core\PostType' ) ? \ModPress\Includes\Core\PostType::get_post_type_names() : array( 'modpress_page' );
		return in_array( $post->post_type, $post_types, true ) ? self::page_url( $post ) : $link;
	}
	/**
	 * Get the URL path for a specific wiki page.
	 *
	 * @param \WP_Post $page The wiki page post object.
	 * @return string The URL path of the wiki page.
	 * @since 1.0.0
	 */
	private static function page_url_path( \WP_Post $page ): string {
		return self::expand( self::pattern_for_object( (int) $page->ID ), $page );
	}

	/**
	 * Tell whether a string is a token placeholder such as %custom_token%.
	 *
	 * @param string $segment Segment to inspect.
	 * @return bool
	 */
	private static function is_token( string $segment ): bool {
		return 1 === preg_match( '/^%[A-Za-z0-9_-]+%$/', $segment );
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

	/**
	 * Resolve the category or tag path for a post using the current post type taxonomy metadata.
	 *
	 * @param \WP_Post $post Post to resolve.
	 * @param bool     $hierarchical Whether a hierarchical taxonomy path is requested.
	 * @return string
	 */
	private static function term_path_for_post( \WP_Post $post, bool $hierarchical = true ): string {
		$taxonomies = get_object_taxonomies( $post->post_type, 'names' );
		foreach ( (array) $taxonomies as $taxonomy ) {
			$taxonomy_object = get_taxonomy( $taxonomy );
			if ( ! $taxonomy_object ) {
				continue;
			}

			if ( $hierarchical && ! $taxonomy_object->hierarchical ) {
				continue;
			}

			if ( ! $hierarchical && $taxonomy_object->hierarchical ) {
				continue;
			}

			if ( 'category' === $taxonomy || 'post_tag' === $taxonomy || 'post_format' === $taxonomy ) {
				continue;
			}

			$path = self::term_path( $taxonomy, (int) $post->ID );
			if ( '' !== $path ) {
				return $path;
			}
		}

		return '';
	}
}









