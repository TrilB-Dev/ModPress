<?php
/**
 * Core mod grouping service.
 *
 * @package ModPress
 * @subpackage Includes\ModManagement
 * @since 1.0.0
 */
namespace ModPress\Includes\ModManagement;

use ModPress\Includes\Core\Taxonomy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GroupsManager {
	/**
	 * Get the taxonomy definitions registered for mods.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_registered_taxonomies(): array {
		$taxonomies = Taxonomy::definitions();
		if ( empty( $taxonomies ) ) {
			$taxonomies = array(
				'modpress_mod_group' => array(
					'label'         => __( 'Mod Groups', 'modpress' ),
					'object_type'   => array( 'modpress_mod' ),
					'hierarchical'  => true,
					'show_in_rest'  => true,
					'public'        => true,
					'show_ui'       => true,
					'show_in_menu'  => true,
					'description'   => __( 'Taxonomy groups for mod organization.', 'modpress' ),
				),
				'modpress_mod_tag' => array(
					'label'         => __( 'Mod Tags', 'modpress' ),
					'object_type'   => array( 'modpress_mod' ),
					'hierarchical'  => false,
					'show_in_rest'  => true,
					'public'        => true,
					'show_ui'       => true,
					'show_in_menu'  => true,
					'description'   => __( 'Tags used to classify mods.', 'modpress' ),
				),
			);
		}

		return $taxonomies;
	}

	/**
	 * Get a grouped view of the registered taxonomy structure.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_group_tree(): array {
		$groups = array();
		$items  = array();

		foreach ( $this->get_registered_taxonomies() as $taxonomy_key => $taxonomy_config ) {
			$taxonomy_object = get_taxonomy( $taxonomy_key );
			$label           = $taxonomy_config['label'] ?? $taxonomy_object->labels->name ?? ucfirst( str_replace( array( '-', '_' ), ' ', $taxonomy_key ) );
			$parent_group    = $taxonomy_config['parent_group'] ?? $taxonomy_config['group'] ?? 'modpress_group_root';
			$group_key       = is_string( $parent_group ) && '' !== $parent_group ? $parent_group : 'modpress_group_root';

			if ( ! isset( $groups[ $group_key ] ) ) {
				$groups[ $group_key ] = array(
					'key'   => $group_key,
					'label' => 'modpress_group_root' === $group_key ? __( 'Mod Groups', 'modpress' ) : ucfirst( str_replace( array( '-', '_' ), ' ', $group_key ) ),
					'items' => array(),
				);
			}

			$groups[ $group_key ]['items'][] = array(
				'key'       => $taxonomy_key,
				'taxonomy'  => $taxonomy_key,
				'label'     => $label,
				'group'     => $group_key,
				'hierarchical' => ! empty( $taxonomy_config['hierarchical'] ),
				'count'     => $this->get_taxonomy_term_count( $taxonomy_key ),
			);
		}

		foreach ( $groups as $group ) {
			$items[] = $group;
		}

		return $items;
	}

	/**
	 * Register a new mod taxonomy from dynamic UI input.
	 *
	 * @param array<string, mixed> $data Dynamic taxonomy payload.
	 * @return bool
	 */
	public function dynamically_create( array $data = array() ): bool {
		$taxonomy      = $data['taxonomy'] ?? $data['slug'] ?? '';
		$object_type   = $data['object_type'] ?? $data['object_types'] ?? array( 'modpress_mod' );
		$group_key     = $data['group'] ?? $data['parent_group'] ?? 'modpress_group_root';
		$taxonomy_data = array(
			'object_type' => $object_type,
			'public'      => true,
			'show_ui'     => true,
			'show_in_rest' => true,
			'group'       => $group_key,
			'parent_group' => $group_key,
		);

		if ( ! empty( $data['label'] ) ) {
			$taxonomy_data['label'] = $data['label'];
		}

		if ( ! empty( $data['description'] ) ) {
			$taxonomy_data['description'] = $data['description'];
		}

		if ( ! empty( $data['hierarchical'] ) ) {
			$taxonomy_data['hierarchical'] = (bool) $data['hierarchical'];
		}

		if ( ! empty( $data['rewrite'] ) ) {
			$taxonomy_data['rewrite'] = $data['rewrite'];
		}

		if ( empty( $taxonomy ) ) {
			return false;
		}

		$created = Taxonomy::dynamically_create( array_merge( $taxonomy_data, array( 'taxonomy' => $taxonomy ) ) );
		if ( ! $created ) {
			return false;
		}

		register_taxonomy_for_object_type( $taxonomy, 'modpress_mod' );

		return true;
	}

	/**
	 * Return the number of terms attached to a taxonomy.
	 *
	 * @param string $taxonomy_key Taxonomy slug.
	 * @return int
	 */
	private function get_taxonomy_term_count( string $taxonomy_key ): int {
		if ( ! taxonomy_exists( $taxonomy_key ) ) {
			return 0;
		}

		$count = wp_count_terms( $taxonomy_key, array( 'hide_empty' => false ) );
		if ( is_array( $count ) ) {
			return count( $count );
		}

		return is_numeric( $count ) ? (int) $count : 0;
	}
}
