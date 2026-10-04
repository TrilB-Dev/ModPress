<?php
/**
 * Core mod management service.
 *
 * @package ModPress
 * @subpackage Includes\ModManagement
 * @since 1.0.0
 */
namespace ModPress\Includes\ModManagement;

use ModPress\Includes\Core\PostType;
use ModPress\Includes\Core\Taxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModManagement {
	/**
	 * Whether the core mod registry has been registered.
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Ensure any database-driven plugin types are registered.
	 *
	 * Built-in ModPress content is registered in Pages\PostTypes and
	 * Pages\Taxonomies only. Dynamic plugin definitions may still be created from
	 * the database and registered here.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( self::$registered ) {
			return;
		}

		if ( class_exists( PostType::class ) ) {
			$dynamic_post_types = self::get_post_type_definitions();
			if ( ! empty( $dynamic_post_types ) ) {
				PostType::register_post_types( $dynamic_post_types );
			}
		}

		if ( class_exists( Taxonomy::class ) ) {
			$dynamic_taxonomies = self::get_taxonomy_definitions();
			if ( ! empty( $dynamic_taxonomies ) ) {
				Taxonomy::register_taxonomies( $dynamic_taxonomies );
			}
		}

		self::$registered = true;
	}

	/**
	 * Dynamic post type definitions created from plugin or database metadata.
	 *
	 * Built-in post types remain in Pages\PostTypes and are intentionally excluded
	 * from this registry.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_post_type_definitions(): array {
		$definitions = apply_filters( 'modpress_dynamic_post_type_definitions', array() );

		return is_array( $definitions ) ? $definitions : array();
	}

	/**
	 * Dynamic taxonomy definitions created from plugin or database metadata.
	 *
	 * Built-in taxonomies remain in Pages\Taxonomies and are intentionally excluded
	 * from this registry.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_taxonomy_definitions(): array {
		$definitions = apply_filters( 'modpress_dynamic_taxonomy_definitions', array() );

		return is_array( $definitions ) ? $definitions : array();
	}

	/**
	 * Get the mod post type identifiers that should be queried.
	 *
	 * @return array<int, string>
	 */
	public static function get_mod_post_types(): array {
		$types = array_keys( self::get_post_type_definitions() );
		if ( ! empty( PostType::get_post_type_names() ) ) {
			$types = PostType::get_post_type_names();
		}

		return array_values( array_unique( array_filter( $types ) ) );
	}

	/**
	 * Get the current mod dashboard summary.
	 *
	 * @return array<string, int>
	 */
	public function get_dashboard_summary(): array {
		$all_mods = $this->get_mods();
		$summary  = array(
			'total'    => 0,
			'published' => 0,
			'draft'    => 0,
		);

		foreach ( $all_mods as $mod ) {
			$summary['total']++;

			if ( 'publish' === $mod->post_status ) {
				$summary['published']++;
			} else {
				$summary['draft']++;
			}
		}

		return $summary;
	}

	/**
	 * Get the available mods.
	 *
	 * @param array<string, mixed> $args Additional query arguments.
	 * @return array<int, \WP_Post>
	 */
	public function get_mods( array $args = array() ): array {
		$defaults = array(
			'post_type'      => self::get_mod_post_types(),
			'post_status'    => 'any',
			'posts_per_page' => 20,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$query = array_merge( $defaults, $args );

		$posts = get_posts( $query );

		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Get a summary for a single mod record.
	 *
	 * @param int $mod_id Mod identifier.
	 * @return array<string, mixed>
	 */
	public function get_mod_summary( int $mod_id ): array {
		$post = get_post( $mod_id );
		if ( ! $post instanceof \WP_Post ) {
			return array(
				'id'          => $mod_id,
				'title'       => __( 'Unknown mod', 'modpress' ),
				'status'      => 'draft',
				'version'     => '0.0.0',
				'description' => '',
			);
		}

		return array(
			'id'          => $post->ID,
			'title'       => get_the_title( $post ),
			'status'      => $post->post_status,
			'version'     => get_post_meta( $post->ID, '_modpress_version', true ) ?: '0.0.0',
			'description' => wp_trim_words( wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ), 24 ),
		);
	}
}
