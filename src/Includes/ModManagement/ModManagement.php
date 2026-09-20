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
	 * Ensure the mod post type and taxonomy registrations are available.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( self::$registered ) {
			return;
		}

		if ( class_exists( PostType::class ) ) {
			PostType::register_post_types( self::get_post_type_definitions() );
		}

		if ( class_exists( Taxonomy::class ) ) {
			Taxonomy::register_taxonomies( self::get_taxonomy_definitions() );
		}

		self::$registered = true;
	}

	/**
	 * Default mod post type definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_post_type_definitions(): array {
		$default_taxonomies = array( 'modpress_mod_group', 'modpress_mod_tag' );
		$dynamic_taxonomies = array();

		foreach ( Taxonomy::definitions() as $taxonomy_key => $taxonomy_config ) {
			$object_types = $taxonomy_config['object_type'] ?? $taxonomy_config['object_types'] ?? array();
			$object_types = is_array( $object_types ) ? $object_types : array( $object_types );

			if ( in_array( 'modpress_mod', $object_types, true ) ) {
				$dynamic_taxonomies[] = $taxonomy_key;
			}
		}

		$taxonomies = array_values(
			array_unique(
				array_merge( $default_taxonomies, $dynamic_taxonomies )
			)
		);

		return array(
			'modpress_mod' => array(
				'label'           => __( 'Mods', 'modpress' ),
				'description'     => __( 'Mod entries managed by ModPress.', 'modpress' ),
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'taxonomies'      => $taxonomies,
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_position'   => 20,
				'menu_icon'       => 'dashicons-archive',
				'has_archive'     => true,
				'show_in_rest'    => true,
				'rewrite'         => array( 'slug' => 'mods' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			),
		);
	}

	/**
	 * Default mod taxonomy definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_taxonomy_definitions(): array {
		$default_taxonomies = array(
			'modpress_mod_group' => array(
				'label'             => __( 'Mod Groups', 'modpress' ),
				'description'       => __( 'Groups used to organize mod entries.', 'modpress' ),
				'object_type'       => array( 'modpress_mod' ),
				'hierarchical'      => true,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'mod-group' ),
			),
			'modpress_mod_tag' => array(
				'label'             => __( 'Mod Tags', 'modpress' ),
				'description'       => __( 'Tags used to classify mod entries.', 'modpress' ),
				'object_type'       => array( 'modpress_mod' ),
				'hierarchical'      => false,
				'public'            => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'mod-tag' ),
			),
		);

		$dynamic_taxonomies = Taxonomy::definitions();
		foreach ( $dynamic_taxonomies as $taxonomy_key => $taxonomy_config ) {
			if ( ! array_key_exists( $taxonomy_key, $default_taxonomies ) ) {
				$default_taxonomies[ $taxonomy_key ] = $taxonomy_config;
			}
		}

		return $default_taxonomies;
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
