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
use ModPress\Includes\Functions\Helpers\DBHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GroupsManager {
	/**
	 * Get the taxonomy definitions registered for mods.
	 *
	 * @return array<string, array<string, mixed>>
	 * @see Taxonomy::definitions() for the source of registered taxonomies.
	 * @since 1.0.0
	 */
	public function get_registered_taxonomies(): array {
		$taxonomies = Taxonomy::definitions();
		if ( empty( $taxonomies ) ) {
			$taxonomies = array(
				'modpress_mod_type' => array(
					'label'         => __( 'Mod Types', 'modpress' ),
					'post_type'     => array( 'modpress_mod' ),
					'hierarchical'  => true,
					'show_in_rest'  => true,
					'public'        => true,
					'show_ui'       => true,
					'show_in_menu'  => false,
					'description'   => __( 'System taxonomy for mod classification.', 'modpress' ),
					'reserved'      => true,
					'deletable'     => false,
				),
			);
		}

		$filtered = array();
		foreach ( $taxonomies as $taxonomy_key => $taxonomy_config ) {
			if ( $this->is_hidden_taxonomy( $taxonomy_key ) ) {
				continue;
			}

			$filtered[ $taxonomy_key ] = $taxonomy_config;
		}

		return $filtered;
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
			$reserved        = $this->is_reserved_taxonomy( $taxonomy_key, $taxonomy_config );

			if ( ! isset( $groups[ $group_key ] ) ) {
				$groups[ $group_key ] = array(
					'key'   => $group_key,
					'label' => 'modpress_group_root' === $group_key ? __( 'Mod Groups', 'modpress' ) : ucfirst( str_replace( array( '-', '_' ), ' ', $group_key ) ),
					'items' => array(),
				);
			}

			$groups[ $group_key ]['items'][] = array(
				'key'         => $taxonomy_key,
				'taxonomy'    => $taxonomy_key,
				'label'       => $label,
				'group'       => $group_key,
				'hierarchical'=> ! empty( $taxonomy_config['hierarchical'] ),
				'count'       => $this->get_taxonomy_term_count( $taxonomy_key ),
				'reserved'    => $reserved,
				'deletable'   => ! $reserved,
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
		$post_type     = $data['post_type'] ?? $data['post_types'] ?? array( 'modpress_mod' );
		$group_key     = $data['group'] ?? $data['parent_group'] ?? 'modpress_group_root';
		$taxonomy_data = array(
			'post_type'    => $post_type,
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'group'        => $group_key,
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

		$taxonomy_key = 0 === strpos( $taxonomy, 'modpress_' ) ? $taxonomy : 'modpress_' . $taxonomy;
		register_taxonomy_for_object_type( $taxonomy_key, 'modpress_mod' );

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

	/**
	 * Determine whether a taxonomy should be hidden from the Mod Groups manager.
	 *
	 * @param string $taxonomy_key Taxonomy slug.
	 * @return bool
	 */
	private function is_hidden_taxonomy( string $taxonomy_key ): bool {
		return in_array( $taxonomy_key, array( 'modpress_mod_tag' ), true );
	}

	/**
	 * Determine whether a taxonomy is a plugin-reserved taxonomy that cannot be deleted.
	 *
	 * @param string $taxonomy_key Taxonomy slug.
	 * @param array<string, mixed> $taxonomy_config Taxonomy config.
	 * @return bool
	 */
	private function is_reserved_taxonomy( string $taxonomy_key, array $taxonomy_config = array() ): bool {
		if ( ! empty( $taxonomy_config['reserved'] ) ) {
			return true;
		}

		return in_array( $taxonomy_key, array( 'modpress_mod_type' ), true );
	}
	/**
	 * Retrieve a term field or metadata value for a group entry.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $field Field key to read.
	 * @return mixed
	 * @since 1.0.0
	 */
	public static function get_group_entry( int $term_id, string $taxonomy, string $field ) {
		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		$property_map = array(
			'name'        => $term->name ?? '',
			'slug'        => $term->slug ?? '',
			'description' => $term->description ?? '',
			'parent'      => $term->parent ?? 0,
			'term_id'     => $term->term_id ?? 0,
		);

		if ( array_key_exists( $field, $property_map ) ) {
			return $property_map[ $field ];
		}

		$value = get_term_meta( $term_id, $field, true );
		if ( '' === $value || null === $value || false === $value ) {
			return '';
		}

		return $value;
	}

	public static function get_group( string $group_id ) {
		$group_id = absint( $group_id );
		if ( $group_id > 0 ) {
			$group = DBHelper::get_by_id( 'groups', $group_id );
			if ( ! empty( $group ) ) {
				return $group;
			}
		}

		$default_group = self::get_default_group();
		if ( ! empty( $default_group ) ) {
			return $default_group;
		}

		return array();
	}

	/**
	 * Create the default Mod Type group row if it does not already exist.
	 *
	 * @return bool
	 */
	public static function create_default_group(): bool {
		if ( ! DBHelper::table_exists( 'groups' ) ) {
			if ( ! method_exists( '\ModPress\Includes\Core\Schema', 'groups_table' ) ) {
				return false;
			}
			\ModPress\Includes\Core\Schema::groups_table();
			DBHelper::create_table( 'groups' );
		}

		if ( ! empty( DBHelper::get_row( 'groups', array( 'group_slug' => 'type' ) ) ) ) {
			return true;
		}

		$inserted = DBHelper::insert(
			'groups',
			array(
				'group_name'          => 'Mod Type',
				'group_slug'          => 'type',
				'group_singular_name' => 'Mod Type',
				'group_description'   => 'The Mod Type group is used to categorise your mods by their type.',
				'group_hierarchical'  => 0,
				'group_parent_id'     => null,
				'group_fields'        => null,
				'created_by'          => 1,
				'created_at'          => current_time( 'mysql' ),
				'updated_at'          => current_time( 'mysql' ),
			)
		);

		return false !== $inserted;
	}

	/**
	 * Return the default core group payload used before the database record is available.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_default_group(): array {
		$group = DBHelper::get_row( 'groups', array( 'group_slug' => 'type' ) );
		if ( ! empty( $group ) ) {
			return $group;
		}

		return array(
			'id'                  => 1,
			'group_name'          => 'Mod Type',
			'group_slug'          => 'type',
			'group_singular_name' => 'Mod Type',
			'group_description'   => 'The Mod Type group is used to categorise your mods by their type.',
			'group_hierarchical'  => 0,
			'group_parent_id'     => null,
			'group_fields'        => null,
			'created_by'          => 1,
		);
	}
}
